<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Models\Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

        $disk = 'public';
        $files = $request->file('images');

        DB::transaction(function () use ($files, $disk) {
            foreach ($files as $file) {
                $path = $file->store('images/'.auth()->id(), $disk);

                auth()->user()->images()->create([
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'disk' => $disk,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        });

        $count = count($files);

        return to_route('images.index')->with('success', $count.' '.str('image')->plural($count).' uploaded successfully!');
    }

    public function edit(Image $image): Response
    {
        abort_if($image->user_id !== auth()->id(), HttpResponse::HTTP_FORBIDDEN);

        return Inertia::render('Image/Edit', [
            'image' => $image,
        ]);
    }

    public function update(UpdateImageRequest $request, Image $image): RedirectResponse
    {
        $image->update(['name' => $request->validated('name')]);

        return to_route('images.index')->with('success', 'Image renamed successfully!');
    }

    public function destroy(Image $image): RedirectResponse
    {
        abort_if($image->user_id !== auth()->id(), HttpResponse::HTTP_FORBIDDEN);

        Storage::disk($image->disk)->delete($image->path);
        $image->delete();

        return to_route('images.index')->with('success', 'Image deleted.');
    }
}
