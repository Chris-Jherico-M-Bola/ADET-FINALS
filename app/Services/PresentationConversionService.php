<?php

namespace App\Services;

use App\Models\Presentation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PresentationConversionService
{
    public function __construct(
        private readonly PDFSlideCountService $slideCountService
    ) {
    }

    public function prepareStorage(string $presentationId): string
    {
        $directory = storage_path("app/presentations/{$presentationId}");
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    public function storeSourceFile(string $directory, string $extension, string $contents): string
    {
        $sourcePath = $directory.'/original'.($extension ? '.'.$extension : '');
        File::put($sourcePath, $contents);

        return $sourcePath;
    }

    public function convert(Presentation $presentation): Presentation
    {
        $presentation->forceFill([
            'status' => 'processing',
            'error_message' => null,
        ])->save();

        $extension = strtolower(pathinfo($presentation->original_name, PATHINFO_EXTENSION));
        $pdfPath = $presentation->directory.'/presentation.pdf';
        $stagingDirectory = $presentation->directory.'/conversion-tmp';

        File::ensureDirectoryExists($presentation->directory);
        File::deleteDirectory($stagingDirectory);
        File::ensureDirectoryExists($stagingDirectory);
        File::delete($pdfPath);

        if ($extension === 'pdf') {
            File::copy($presentation->source_path, $pdfPath);
            File::deleteDirectory($stagingDirectory);
            return $this->finalize($presentation, $pdfPath);
        }

        $binary = $this->findLibreOfficeBinary();

        if (! $binary) {
            throw new \RuntimeException('LibreOffice / soffice was not found. Install LibreOffice or set LIBREOFFICE_PATH.');
        }

        try {
            $escapedBinary = str_replace("'", "''", $binary);
            $escapedOutdir = str_replace("'", "''", $stagingDirectory);
            $escapedSource = str_replace("'", "''", $presentation->source_path);
            $command = sprintf(
                'powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -Command "& \'%s\' --headless --nologo --nofirststartwizard --norestore --convert-to pdf --outdir \'%s\' \'%s\'" 2>&1',
                $escapedBinary,
                $escapedOutdir,
                $escapedSource
            );

            $output = [];
            $exitCode = 0;
            exec($command, $output, $exitCode);

            $generatedPdf = $stagingDirectory.'/'.pathinfo($presentation->source_path, PATHINFO_FILENAME).'.pdf';

            if (! is_file($generatedPdf)) {
                if ($exitCode !== 0) {
                    throw new \RuntimeException(trim(implode(PHP_EOL, $output) ?: 'Conversion failed.'));
                }

                throw new \RuntimeException(trim(implode(PHP_EOL, $output) ?: 'LibreOffice did not produce a PDF output file.'));
            }

            File::move($generatedPdf, $pdfPath);

            return $this->finalize($presentation, $pdfPath);
        } finally {
            File::deleteDirectory($stagingDirectory);
        }
    }

    public function deleteStorage(Presentation $presentation): void
    {
        if (is_dir($presentation->directory)) {
            File::deleteDirectory($presentation->directory);
        }
    }

    public function retry(Presentation $presentation): Presentation
    {
        return $this->convert($presentation->refresh());
    }

    private function finalize(Presentation $presentation, string $pdfPath): Presentation
    {
        $presentation->forceFill([
            'pdf_path' => $pdfPath,
            'slide_count' => $this->slideCountService->count($pdfPath),
            'status' => 'ready',
            'converted_at' => now(),
        ])->save();

        return $presentation->refresh();
    }

    private function findLibreOfficeBinary(): ?string
    {
        $candidates = array_values(array_filter([
            env('LIBREOFFICE_PATH'),
            env('SOFFICE_PATH'),
            $this->which('soffice'),
            $this->which('soffice.com'),
            $this->which('libreoffice'),
            $this->which('libreoffice.com'),
            'C:\\Program Files\\LibreOffice\\program\\soffice.com',
            'C:\\Program Files\\LibreOffice\\program\\libreoffice.com',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\LibreOffice\\program\\libreoffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\libreoffice.exe',
        ]));

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && (str_contains($candidate, '.exe') || str_contains($candidate, '.com') ? is_file($candidate) : true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function which(string $command): ?string
    {
        $process = Process::fromShellCommandline(PHP_OS_FAMILY === 'Windows' ? "where {$command}" : "command -v {$command}");
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());

        if ($output === '') {
            return null;
        }

        return Str::of($output)->before(PHP_EOL)->trim()->toString();
    }
}
