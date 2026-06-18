<?php

namespace App\Services;

use App\Models\Presentation;
use Illuminate\Support\Facades\File;

/**
 * Storage abstraction with automatic Supabase → local fallback.
 *
 * When SUPABASE_PROJECT + SUPABASE_SECRET are configured, files are
 * uploaded to Supabase Storage first. If Supabase is unreachable or
 * returns an error, the operation transparently falls back to the
 * local filesystem under storage/app/presentations/.
 *
 * When no Supabase credentials are present, the service operates
 * exclusively against the local filesystem — ideal for development
 * or fully-local deployments.
 *
 * File paths (source_path, pdf_path, notes_path) stored in the
 * database are identical regardless of the active driver:
 *   {uuid}/original.pptx
 *   {uuid}/presentation.pdf
 *   {uuid}/notes.md
 */
class StorageService
{
    private const SUPABASE_DRIVER = 'supabase';
    private const LOCAL_DRIVER    = 'local';

    // ──────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────

    /**
     * The active storage driver key.
     */
    public function activeDriver(): string
    {
        return $this->isSupabaseConfigured()
            ? self::SUPABASE_DRIVER
            : self::LOCAL_DRIVER;
    }

    /**
     * Upload a file. Tries Supabase first, falls back to local copy.
     *
     * @param  string  $localPath  Absolute path to the source file on disk.
     * @param  string  $destPath   Relative key (e.g. "{uuid}/original.pptx").
     * @return string              The destPath on success.
     */
    public function upload(string $localPath, string $destPath): string
    {
        if ($this->isSupabaseConfigured()) {
            try {
                return SupabaseStorageService::upload($localPath, $destPath);
            } catch (\RuntimeException) {
                // Supabase failed — fall through to local
            }
        }

        $localDest = $this->localAbsolutePath($destPath);
        File::ensureDirectoryExists(\dirname($localDest));
        File::copy($localPath, $localDest);

        return $destPath;
    }

    /**
     * Read a file's contents. Tries Supabase first, falls back to local.
     *
     * @throws \RuntimeException when the file cannot be found in any location.
     */
    public function get(string $path): string
    {
        if ($this->isSupabaseConfigured()) {
            try {
                return SupabaseStorageService::get($path);
            } catch (\RuntimeException) {
                // Supabase failed — fall through to local
            }
        }

        $localPath = $this->localAbsolutePath($path);

        if (! is_file($localPath)) {
            throw new \RuntimeException("File not found in any storage location: {$path}");
        }

        $contents = file_get_contents($localPath);

        if ($contents === false) {
            throw new \RuntimeException("Failed to read local file: {$localPath}");
        }

        return $contents;
    }

    /**
     * Delete all files belonging to a presentation from every
     * storage location they might exist in.
     */
    public function deletePresentationFiles(Presentation $presentation): void
    {
        $paths = array_filter([
            $presentation->source_path,
            $presentation->pdf_path,
            $presentation->notes_path,
        ]);

        if ($paths === []) {
            return;
        }

        // Clean Supabase (best-effort — don't throw on 404s)
        if ($this->isSupabaseConfigured()) {
            try {
                SupabaseStorageService::deletePresentationFiles($presentation);
            } catch (\RuntimeException) {
                // Ignore — local cleanup still runs below
            }
        }

        // Clean local filesystem
        foreach ($paths as $path) {
            $localPath = $this->localAbsolutePath($path);
            if (is_file($localPath)) {
                File::delete($localPath);
            }
        }

        // Remove the empty directory if it exists
        $localDir = $this->localAbsolutePath($presentation->id);
        if (is_dir($localDir)) {
            File::deleteDirectory($localDir);
        }
    }

    /**
     * Generate a public-facing URL for the given storage path.
     *
     * In Supabase mode this returns the direct Supabase public object URL.
     * In local mode it returns the application's proxy route so files
     * are served through the Laravel application.
     */
    public function publicUrl(string $path, Presentation $presentation): string
    {
        if ($this->isSupabaseConfigured()) {
            return SupabaseStorageService::publicUrl($path);
        }

        // Local mode — route through the application's proxy endpoints
        $route = match (true) {
            str_contains($path, '/notes.md')     => 'presentations.notes',
            str_contains($path, '/original.')    => 'presentations.pptx',
            default                              => 'presentations.pdf',
        };

        return route($route, $presentation);
    }

    /**
     * Remove an entire local working directory for a presentation.
     */
    public function cleanLocalDirectory(string $uuid): void
    {
        $dir = $this->localAbsolutePath($uuid);
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }
    }

    // ──────────────────────────────────────────────
    //  Internal helpers
    // ──────────────────────────────────────────────

    private function isSupabaseConfigured(): bool
    {
        return (bool) (config('supabase.project') && config('supabase.secret'));
    }

    private function localAbsolutePath(string $path): string
    {
        // Legacy records may have absolute paths in the database
        // (e.g. /var/www/.../storage/app/presentations/{uuid}/original.pptx).
        // New records store relative keys (e.g. {uuid}/original.pptx).
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return storage_path('app/presentations/'.$path);
    }
}
