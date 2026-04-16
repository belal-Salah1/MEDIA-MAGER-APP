<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\UpdateVideoRequest;
use App\Models\Video;
use getID3;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class VideoController extends Controller
{
    public function index(): Response
    {
        $videos = auth()->user()
            ->videos()
            ->latest()
            ->paginate(24);

        return Inertia::render('Video/Index', [
            'videos' => $videos,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Video/Create');
    }

    public function store(StoreVideoRequest $request): RedirectResponse
    {
        $disk = 'public';
        $files = $request->file('videos');
        $getID3 = new getID3;

        DB::transaction(function () use ($files, $disk, $getID3) {
            foreach ($files as $file) {
                $path = $file->store('videos/'.auth()->id(), $disk);

                $metadata = $getID3->analyze($file->getRealPath());
                $videoStream = $metadata['video'] ?? [];

                auth()->user()->videos()->create([
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'disk' => $disk,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'duration' => isset($metadata['playtime_seconds']) ? (int) $metadata['playtime_seconds'] : null,
                    'width' => isset($videoStream['resolution_x']) ? (int) $videoStream['resolution_x'] : null,
                    'height' => isset($videoStream['resolution_y']) ? (int) $videoStream['resolution_y'] : null,
                ]);
            }
        });

        $count = count($files);

        return to_route('videos.index')->with('success', $count.' '.str('video')->plural($count).' uploaded successfully!');
    }

    public function edit(Video $video): Response
    {
        abort_if($video->user_id !== auth()->id(), HttpResponse::HTTP_FORBIDDEN);

        return Inertia::render('Video/Edit', [
            'video' => $video,
        ]);
    }

    public function update(UpdateVideoRequest $request, Video $video): RedirectResponse
    {
        $video->update(['name' => $request->validated('name')]);

        return to_route('videos.index')->with('success', 'Video renamed successfully!');
    }

    public function destroy(Video $video): RedirectResponse
    {
        abort_if($video->user_id !== auth()->id(), HttpResponse::HTTP_FORBIDDEN);

        Storage::disk($video->disk)->delete($video->path);
        $video->delete();

        return to_route('videos.index')->with('success', 'Video deleted.');
    }
}
