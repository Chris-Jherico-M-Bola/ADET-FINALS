<?php

namespace App\Services;

use App\Models\Presentation;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class PresentationConversionService
{
    private readonly string $nodeScript;

    public function __construct(
        private readonly PDFSlideCountService $slideCountService,
    ) {
        $this->nodeScript = base_path('bin/convert-pptx.cjs');
    }

    /**
     * Create a local working directory for a presentation.
     */
    public function prepareStorage(string $presentationId): string
    {
        $directory = storage_path("app/presentations/{$presentationId}");
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /**
     * Convert a presentation. The source file must exist at $localSourcePath.
     * On success, source + PDF are uploaded to Supabase, local files are
     * cleaned up, and the presentation record is updated with remote paths.
     */
    public function convert(Presentation $presentation, string $localSourcePath): Presentation
    {
        $presentation->forceFill([
            'status' => 'processing',
            'error_message' => null,
        ])->save();

        $uuid = $presentation->id;
        $localDir = dirname($localSourcePath);
        $extension = strtolower(pathinfo($presentation->original_name, PATHINFO_EXTENSION));
        $localPdfPath = "{$localDir}/presentation.pdf";

        if ($extension === 'pdf') {
            File::copy($localSourcePath, $localPdfPath);
        } else {
            $process = new Process([
                $this->findNodeBinary(),
                $this->nodeScript,
                '--input', $localSourcePath,
                '--output', $localPdfPath,
            ]);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($localPdfPath)) {
                $errorOutput = trim($process->getErrorOutput()) ?: 'PPTX-to-PDF conversion failed.';
                throw new \RuntimeException($errorOutput);
            }
        }

        // Upload source + PDF to Supabase Storage
        $sourceKey = "{$uuid}/original.{$extension}";
        $pdfKey = "{$uuid}/presentation.pdf";

        SupabaseStorageService::upload($localSourcePath, $sourceKey);
        SupabaseStorageService::upload($localPdfPath, $pdfKey);

        // Clean up local working directory
        File::deleteDirectory($localDir);

        // Count slides from the uploaded PDF
        $tempPdf = tempnam(sys_get_temp_dir(), 'slidecount_').'.pdf';
        file_put_contents($tempPdf, SupabaseStorageService::get($pdfKey));
        $slideCount = $this->slideCountService->count($tempPdf);
        File::delete($tempPdf);

        $presentation->forceFill([
            'source_path' => $sourceKey,
            'pdf_path' => $pdfKey,
            'slide_count' => $slideCount,
            'status' => 'ready',
            'converted_at' => now(),
        ])->save();

        return $presentation->refresh();
    }

    /**
     * Retry conversion by downloading the source from Supabase first.
     */
    public function retry(Presentation $presentation): Presentation
    {
        $uuid = $presentation->id;
        $localDir = $this->prepareStorage($uuid);
        $extension = strtolower(pathinfo($presentation->original_name, PATHINFO_EXTENSION));
        $localSourcePath = "{$localDir}/original.{$extension}";

        // Download source from Supabase
        file_put_contents(
            $localSourcePath,
            SupabaseStorageService::get($presentation->source_path)
        );

        return $this->convert($presentation, $localSourcePath);
    }

    /**
     * Delete all presentation files from Supabase.
     */
    public function deleteStorage(Presentation $presentation): void
    {
        SupabaseStorageService::deletePresentationFiles($presentation);
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
