<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Catalog\MediaUploadRequest;
use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MediaController extends AdminController
{
    public function index(Request $request): View|JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $media = Media::query()
            ->where('collection', 'library')   // review photos etc. stay out of the library
            ->withCount('products')
            ->when($term !== '', fn ($q) => $q->where('original_name', 'like', '%'.$term.'%'))
            ->latest('id')
            ->paginate($request->query('format') === 'json' ? 48 : 36)
            ->withQueryString();

        // The image picker in the product and category forms loads the library as JSON.
        if ($request->query('format') === 'json') {
            return response()->json([
                'data' => $media->getCollection()->map->toPickerArray(),
                'next' => $media->nextPageUrl(),
            ]);
        }

        return view('admin.catalog.media.index', compact('media', 'term'));
    }

    public function store(MediaUploadRequest $request, MediaService $service): JsonResponse|RedirectResponse
    {
        try {
            $media = $service->upload($request->file('file'), (array) $request->input('alt', []));
        } catch (RuntimeException $e) {
            report($e);
            $message = \App\Services\Media\ImageProcessor::isSupported() ? __('admin.media.upload_failed') : __('admin.media.gd_missing');

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'errors' => ['file' => [$message]]], 422)
                : back()->with('error', $message);
        }

        return $request->expectsJson()
            ? response()->json($media->toPickerArray(), 201)
            : back()->with('success', __('admin.media.uploaded'));
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $data = $request->validate([
            'alt' => ['nullable', 'array'],
            'alt.*' => ['nullable', 'string', 'max:200'],
        ]);

        $media->alt = array_filter($data['alt'] ?? []);
        $media->save();

        return back()->with('success', __('admin.media.saved'));
    }

    public function destroy(Media $media, MediaService $service): RedirectResponse
    {
        $service->delete($media);

        return back()->with('success', __('admin.media.deleted'));
    }
}
