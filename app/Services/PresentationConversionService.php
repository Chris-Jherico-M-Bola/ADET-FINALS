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
        private readonly StorageService $storageService,
    ) {
        $this->nodeScript = base_path('bin/convert-pptx.cjs');
    }

    /**
     * Create a local working directory for a presentation.
     * Uses a temp directory so the subsequent upload-to-storage
     * is a true copy, not a no-op (important for local-only mode).
     */
    public function prepareStorage(string $presentationId): string
    {
        $directory = sys_get_temp_dir().'/presentations/'.$presentationId;
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

        // Upload source + PDF to storage (Supabase → local fallback)
        $sourceKey = "{$uuid}/original.{$extension}";
        $pdfKey = "{$uuid}/presentation.pdf";

        $this->storageService->upload($localSourcePath, $sourceKey);
        $this->storageService->upload($localPdfPath, $pdfKey);

        // Count slides from the local PDF *before* cleanup so it works
        // in both Supabase and local-only modes.
        $slideCount = $this->slideCountService->count($localPdfPath);

        // Clean up the temporary working directory
        File::deleteDirectory($localDir);

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

        // Download source from storage (Supabase → local fallback)
        file_put_contents(
            $localSourcePath,
            $this->storageService->get($presentation->source_path)
        );

        return $this->convert($presentation, $localSourcePath);
    }

    /**
     * Delete all presentation files from Supabase.
     */
    public function deleteStorage(Presentation $presentation): void
    {
        $this->storageService->deletePresentationFiles($presentation);
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
