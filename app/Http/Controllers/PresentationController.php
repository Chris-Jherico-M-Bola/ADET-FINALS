<?php

namespace App\Http\Controllers;

use App\Models\Presentation;
use App\Services\PresentationConversionService;
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
        $directory = $this->conversionService->prepareStorage($presentationId);
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $sourcePath = $directory.'/original.'.$extension;

        $file->move($directory, 'original.'.$extension);

        $presentation = Presentation::create([
            'id' => $presentationId,
            'title' => trim($data['title'] ?? '') !== '' ? trim($data['title']) : pathinfo($originalName, PATHINFO_FILENAME),
            'original_name' => $originalName,
            'directory' => $directory,
            'source_path' => $sourcePath,
            'pdf_path' => $directory.'/presentation.pdf',
            'slide_count' => 0,
            'status' => 'processing',
            'error_message' => null,
            'converted_at' => null,
        ]);

        try {
            $presentation = $this->conversionService->convert($presentation);
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

    public function pdf(Presentation $presentation)
    {
        abort_unless($presentation->status === 'ready' && is_file($presentation->pdf_path), 404);

        return response()->file($presentation->pdf_path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$presentation->title.'.pdf"',
        ]);
    }

    public function pptx(Presentation $presentation)
    {
        abort_unless(is_file($presentation->source_path), 404);

        return response()->download($presentation->source_path, $presentation->original_name);
    }

    private function presenterPayload(Presentation $presentation): array
    {
        return [
            'id' => $presentation->id,
            'title' => $presentation->title,
            'original_name' => $presentation->original_name,
            'directory' => $presentation->directory,
            'source_path' => $presentation->source_path,
            'pdf_path' => $presentation->pdf_path,
            'slide_count' => $presentation->slide_count ?? 0,
            'status' => $presentation->status,
            'error_message' => $presentation->error_message,
            'converted_at' => optional($presentation->converted_at)?->toISOString(),
            'created_at' => $presentation->created_at?->toISOString(),
            'updated_at' => $presentation->updated_at?->toISOString(),
            'pdf_url' => route('presentations.pdf', $presentation),
            'pptx_url' => route('presentations.pptx', $presentation),
        ];
    }
}
