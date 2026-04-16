<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePdfRequest;
use App\Models\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PdfController extends Controller
{
    public function index(): Response
    {
        $pdfs = auth()->user()
            ->pdfs()
            ->latest()
            ->paginate(24);

        return Inertia::render('Pdf/Index', [
            'pdfs' => $pdfs,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Pdf/Create');
    }

    public function store(StorePdfRequest $request): RedirectResponse
    {
        $file = $request->file('pdf');
        $disk = 'public';
        $path = $file->store('pdfs/'.auth()->id(), $disk);

        auth()->user()->pdfs()->create([
            'name' => $request->input('name') ?: $file->getClientOriginalName(),
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return to_route('pdfs.index')->with('success', 'PDF uploaded successfully!');
    }

    public function destroy(Pdf $pdf): RedirectResponse
    {
        abort_if($pdf->user_id !== auth()->id(), HttpResponse::HTTP_FORBIDDEN);

        Storage::disk($pdf->disk)->delete($pdf->path);
        $pdf->delete();

        return to_route('pdfs.index')->with('success', 'PDF deleted.');
    }
}
