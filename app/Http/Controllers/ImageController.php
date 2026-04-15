<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Models\Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ImageController extends Controller
{
    public function index(): Response
    {
        $images = auth()->user()
            ->images()
            ->latest()
            ->paginate(24);

        return Inertia::render('Image/Index', [
            'images' => $images,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Image/Create');
    }

    public function store(StoreImageRequest $request): RedirectResponse
    {
        $request->validated();

        $file = $request->file('image');
        $disk = 'public';
        $path = $file->store('images/'.auth()->id(), $disk);

        auth()->user()->images()->create([
            'name' => $request->input('name') ?: $file->getClientOriginalName(),
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return to_route('images.index')->with('success', 'Image uploaded successfully!');
    }

    public function destroy(Image $image): RedirectResponse
    {
        abort_if($image->user_id !== auth()->id(), HttpResponse::HTTP_FORBIDDEN);

        Storage::disk($image->disk)->delete($image->path);
        $image->delete();

        return to_route('images.index')->with('success', 'Image deleted.');
    }
}
