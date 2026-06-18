<?php

namespace App\Http\Controllers;

use App\Models\Presentation;
use App\Services\PresentationConversionService;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PresentationController extends Controller
{
    public function __construct(
        private readonly PresentationConversionService $conversionService
    ) {
    }

    public function index()
    {
        return response()->json(
            Presentation::query()
                ->latest()
                ->get()
                ->map(fn (Presentation $presentation) => $this->presenterPayload($presentation))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', Rule::file()->extensions(['ppt', 'pptx', 'pdf']), 'max:51200'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $data['file'];
        $presentationId = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();

        // Save uploaded file to a local working directory
        $localDir = $this->conversionService->prepareStorage($presentationId);
        $localSourcePath = $localDir.'/original.'.$extension;
        $file->move($localDir, 'original.'.$extension);

        $presentation = Presentation::create([
            'id' => $presentationId,
            'title' => trim($data['title'] ?? '') !== '' ? trim($data['title']) : pathinfo($originalName, PATHINFO_FILENAME),
            'original_name' => $originalName,
            'directory' => $presentationId,
            'source_path' => $localSourcePath,
            'pdf_path' => '',
            'slide_count' => 0,
            'status' => 'processing',
            'error_message' => null,
            'converted_at' => null,
        ]);

        try {
            $presentation = $this->conversionService->convert($presentation, $localSourcePath);
        } catch (\Throwable $throwable) {
            $presentation->forceFill([
                'status' => 'failed',
                'error_message' => $throwable->getMessage(),
            ])->save();
            $presentation->refresh();
        }

        return response()->json($this->presenterPayload($presentation), 201);
    }

    public function show(Presentation $presentation)
    {
        return response()->json($this->presenterPayload($presentation));
    }

    public function retry(Presentation $presentation)
    {
        try {
            $presentation = $this->conversionService->retry($presentation);
        } catch (\Throwable $throwable) {
            $presentation->forceFill([
                'status' => 'failed',
                'error_message' => $throwable->getMessage(),
            ])->save();
            $presentation->refresh();
        }

        return response()->json($this->presenterPayload($presentation));
    }

    public function destroy(Presentation $presentation)
    {
        $this->conversionService->deleteStorage($presentation);
        $presentation->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Serve the PDF by proxying from Supabase.
     */
    public function pdf(Presentation $presentation)
    {
        abort_unless($presentation->status === 'ready' && $presentation->pdf_path, 404);

        try {
            $content = SupabaseStorageService::get($presentation->pdf_path);
        } catch (\RuntimeException) {
            abort(404);
        }

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$presentation->title.'.pdf"',
        ]);
    }

    /**
     * Serve the source PPTX by proxying from Supabase.
     */
    public function pptx(Presentation $presentation)
    {
        abort_unless($presentation->source_path, 404);

        try {
            $content = SupabaseStorageService::get($presentation->source_path);
        } catch (\RuntimeException) {
            abort(404);
        }

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'Content-Disposition' => 'attachment; filename="'.$presentation->original_name.'"',
        ]);
    }

    /**
     * Serve the notes markdown by proxying from Supabase.
     */
    public function notes(Presentation $presentation)
    {
        abort_unless($presentation->notes_path, 404);

        try {
            $content = SupabaseStorageService::get($presentation->notes_path);
        } catch (\RuntimeException) {
            abort(404);
        }

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Content-Disposition' => 'inline; filename="'.$presentation->title.' - Notes.md"',
        ]);
    }

    public function saveNotes(Request $request, Presentation $presentation)
    {
        $data = $request->validate([
            'notes' => ['required', 'array', 'min:1'],
            'notes.*.text' => ['required', 'string', 'max:10000'],
            'notes.*.slideNumber' => ['required', 'integer', 'min:1'],
            'notes.*.timestamp' => ['required', 'string', 'max:64'],
        ]);

        $lines = [];
        $lines[] = '# Presentation Notes — '.$presentation->title;
        $lines[] = '';
        $lines[] = 'Session recorded on '.now()->format('F j, Y \a\t g:i A');
        $lines[] = '';
        $lines[] = '| # | Time | Slide | Transcript |';
        $lines[] = '|---|------|-------|------------|';

        foreach ($data['notes'] as $index => $entry) {
            $time = date('H:i:s', strtotime($entry['timestamp']));
            $text = str_replace(["\r\n", "\r", "\n"], ' ', $entry['text']);
            $lines[] = '| '.($index + 1).' | '.$time.' | '.$entry['slideNumber'].' | '.$text.' |';
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '_Auto-generated from voice transcript on '.now()->format('Y-m-d H:i:s').'_';

        $content = implode("\n", $lines);
        $notesKey = $presentation->id.'/notes.md';

        // Write to a temp file then upload to Supabase
        $tempPath = tempnam(sys_get_temp_dir(), 'notes_').'.md';
        file_put_contents($tempPath, $content);
        SupabaseStorageService::upload($tempPath, $notesKey);
        @unlink($tempPath);

        $presentation->forceFill(['notes_path' => $notesKey])->save();

        return response()->json([
            'success' => true,
            'notes_url' => route('presentations.notes', $presentation),
        ]);
    }

    private function presenterPayload(Presentation $presentation): array
    {
        $hasNotes = $presentation->notes_path !== null && $presentation->notes_path !== '';

        return [
            'id' => $presentation->id,
            'title' => $presentation->title,
            'original_name' => $presentation->original_name,
            'directory' => $presentation->id,
            'source_path' => $presentation->source_path,
            'pdf_path' => $presentation->pdf_path,
            'slide_count' => $presentation->slide_count ?? 0,
            'status' => $presentation->status,
            'error_message' => $presentation->error_message,
            'converted_at' => optional($presentation->converted_at)?->toISOString(),
            'created_at' => $presentation->created_at?->toISOString(),
            'updated_at' => $presentation->updated_at?->toISOString(),
            'pdf_url' => $presentation->pdf_path ? SupabaseStorageService::publicUrl($presentation->pdf_path) : null,
            'pptx_url' => $presentation->source_path ? SupabaseStorageService::publicUrl($presentation->source_path) : null,
            'notes_url' => $hasNotes ? SupabaseStorageService::publicUrl($presentation->notes_path) : null,
        ];
    }
}
