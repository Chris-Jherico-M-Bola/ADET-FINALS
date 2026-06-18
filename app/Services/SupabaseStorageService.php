<?php

namespace App\Services;

use App\Models\Presentation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class SupabaseStorageService
{
    /**
     * Build the REST API base URL for the configured project.
     */
    private static function baseUrl(): string
    {
        $project = config('supabase.project');

        return "https://{$project}.supabase.co/storage/v1";
    }

    /**
     * Build the public URL for an object in the bucket.
     */
    public static function publicUrl(string $path): string
    {
        $project = config('supabase.project');
        $bucket = rawurlencode(config('supabase.bucket'));

        return "https://{$project}.supabase.co/storage/v1/object/public/{$bucket}/{$path}";
    }

    /**
     * Headers with service_role authorization.
     */
    private static function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.config('supabase.secret'),
            'Content-Type' => 'application/octet-stream',
        ];
    }

    /**
     * Upload a local file to the bucket via the REST API.
     */
    public static function upload(string $localPath, string $destPath): string
    {
        $contents = file_get_contents($localPath);

        if ($contents === false) {
            throw new \RuntimeException("Failed to read local file: {$localPath}");
        }

        $bucket = rawurlencode(config('supabase.bucket'));
        $url = self::baseUrl()."/object/{$bucket}/{$destPath}";

        $response = Http::withHeaders(self::authHeaders())
            ->withBody($contents, 'application/octet-stream')
            ->timeout(120)
            ->withOptions(['expect' => false])
            ->post($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Failed to upload '{$destPath}': {$response->status()} {$response->body()}"
            );
        }

        return $destPath;
    }

    /**
     * Upload an UploadedFile directly to the bucket.
     */
    public static function uploadFile(UploadedFile $file, string $destPath): string
    {
        $bucket = rawurlencode(config('supabase.bucket'));
        $url = self::baseUrl()."/object/{$bucket}/{$destPath}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('supabase.secret'),
        ])->attach('file', $file->get(), $file->getClientOriginalName())
            ->post($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Failed to upload '{$destPath}': {$response->status()} {$response->body()}"
            );
        }

        return $destPath;
    }

    /**
     * Fetch object content from Supabase via the REST API.
     */
    public static function get(string $path): string
    {
        $bucket = rawurlencode(config('supabase.bucket'));
        $url = self::baseUrl()."/object/{$bucket}/{$path}";

        $response = Http::withHeaders(self::authHeaders())->get($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Failed to fetch object '{$path}': {$response->status()} {$response->body()}"
            );
        }

        return $response->body();
    }

    /**
     * Delete a single object from the bucket.
     */
    public static function delete(string $path): void
    {
        $bucket = rawurlencode(config('supabase.bucket'));
        $url = self::baseUrl()."/object/{$bucket}";

        $response = Http::withToken(config('supabase.secret'))
            ->withBody(json_encode(['prefixes' => [$path]]), 'application/json')
            ->delete($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Failed to delete '{$path}': {$response->status()} {$response->body()}"
            );
        }
    }

    /**
     * Delete all files associated with a presentation.
     */
    public static function deletePresentationFiles(Presentation $presentation): void
    {
        $paths = array_filter([
            $presentation->source_path,
            $presentation->pdf_path,
            $presentation->notes_path,
        ]);

        if ($paths === []) {
            return;
        }

        $bucket = rawurlencode(config('supabase.bucket'));
        $url = self::baseUrl()."/object/{$bucket}";

        $response = Http::withToken(config('supabase.secret'))
            ->withBody(json_encode(['prefixes' => $paths]), 'application/json')
            ->delete($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Failed to delete presentation files: '.$response->status().' '.$response->body()
            );
        }
    }
}
