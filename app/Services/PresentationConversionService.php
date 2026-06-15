<?php

namespace App\Services;

use App\Models\Presentation;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class PresentationConversionService
{
    private readonly string $nodeScript;

    public function __construct(
        private readonly PDFSlideCountService $slideCountService
    ) {
        $this->nodeScript = base_path('bin/convert-pptx.cjs');
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

        File::ensureDirectoryExists($presentation->directory);

        if ($extension === 'pdf') {
            File::copy($presentation->source_path, $pdfPath);

            return $this->finalize($presentation, $pdfPath);
        }

        $process = new Process([
            $this->findNodeBinary(),
            $this->nodeScript,
            '--input', $presentation->source_path,
            '--output', $pdfPath,
        ]);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($pdfPath)) {
            $errorOutput = trim($process->getErrorOutput()) ?: 'PPTX-to-PDF conversion failed.';

            throw new \RuntimeException($errorOutput);
        }

        return $this->finalize($presentation, $pdfPath);
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

    private function findNodeBinary(): string
    {
        $process = Process::fromShellCommandline('command -v node');
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        throw new \RuntimeException('Node.js binary not found. Ensure Node.js is installed and on PATH.');
    }
}
