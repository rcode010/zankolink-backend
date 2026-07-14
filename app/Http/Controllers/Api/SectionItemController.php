<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionItemRequest;
use App\Http\Requests\UpdateSectionItemRequest;
use App\Http\Resources\SectionItemResource;
use App\Models\CourseSection;
use App\Models\SectionItem;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @group Course Section Items Management
 *
 * APIs for managing files, documents, and external links within a course section.
 */
class SectionItemController extends Controller
{
    use ApiResponses;

    /**
     * List Section Items
     * Returns all section materials for a given course section.
     *
     * This endpoint is used by the frontend to load the full material list for a
     * section card. Each item is serialized through `SectionItemResource`, so the
     * response is normalized for the UI and includes a frontend-friendly shape
     * such as `title`, `type`, `url`, `content`, and `size`.
     *
     * * @urlParam section int required The ID of the course section. Example: 1
     */
    public function index(Request $request, CourseSection $section)
    {
        $items = $section->items()->latest()->get();

        return $this->ok(
            'Section items retrieved successfully',
            SectionItemResource::collection($items)->toArray($request)
        );
    }

    /**
     * Add Section Item
     * Uploads a physical file or stores an external link as a section material.
     *
     * Use this endpoint when the frontend needs to create a file-based material
     * or a URL-based material inside a course section. The created resource is
     * returned in the normalized `SectionItemResource` format for immediate UI use.
     *
     * * @urlParam section int required The ID of the course section. Example: 1
     *
     * @bodyParam file file The physical document or media file to upload (Max 50MB). Required if url is omitted.
     * @bodyParam url string The full external link/URL. Required if file is omitted. Example: https://example.com/slide.pdf
     * @bodyParam material_file_name string Custom display name for the material. Required only if url is provided. Example: Lecture 1 Slides
     */
    public function store(StoreSectionItemRequest $request, CourseSection $section)
    {
        $validated = $request->validated();

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('section-items', 'public');

            $data['material_file_type'] = $file->getClientOriginalExtension();
            $data['material_file_name'] = $validated['material_file_name'] ?? $file->getClientOriginalName();
            $data['material_file_url'] = $path;
        }

        if (!empty($validated['url'])) {
            $data['material_file_type'] = 'link';
            $data['material_file_name'] = $validated['material_file_name'];
            $data['material_file_url'] = $validated['url'];
        }

        $item = $section->items()->create($data);

        return $this->success(
            'Material added successfully.',
            (new SectionItemResource($item))->toArray($request),
            201
        );
    }

    /**
     * View Section Item
     * Fetches one specific section item by ID.
     *
     * This is used when the frontend opens a material detail view or needs to
     * inspect the normalized payload for a single item.
     *
     * * @urlParam item int required The ID of the section item. Example: 5
     */
    public function show(Request $request, SectionItem $item)
    {
        return $this->ok(
            'Section item retrieved successfully',
            (new SectionItemResource($item))->toArray($request)
        );
    }

    /**
     * Download or Redirect Item
     * Downloads a stored file directly, or redirects the browser to an external
     * URL when the item is a link.
     *
     * * @urlParam item int required The ID of the section item. Example: 5
     */
    public function download(SectionItem $item)
    {
        if ($item->material_file_type === 'link' || Str::startsWith($item->material_file_url, ['http://', 'https://'])) {
            return redirect()->away($item->material_file_url);
        }

        if (! Storage::disk('public')->exists($item->material_file_url)) {
            return $this->error('File not found.', 404);
        }

        return Storage::disk('public')->download($item->material_file_url, $item->material_file_name);
    }

    /**
     * Update Section Item
     * Updates metadata such as the display name for an existing section item.
     *
     * Re-uploading a file should be handled through delete + create flow instead
     * of this metadata-only update endpoint.
     *
     * * @urlParam item int required The ID of the section item. Example: 5
     *
     * @bodyParam material_file_name string The updated custom name for the material. Example: Updated Lecture 1 Slides
     */
    public function update(UpdateSectionItemRequest $request, SectionItem $item)
    {
        $item->update($request->validated());

        return $this->ok(
            'Section item updated successfully.',
            (new SectionItemResource($item->fresh()))->toArray($request)
        );
    }

    /**
     * Delete Section Item
     * Permanently removes a section item and deletes its physical storage file
     * when the item is a local uploaded asset.
     *
     * * @urlParam item int required The ID of the section item. Example: 5
     */
    public function destroy(SectionItem $item)
    {
        if ($item->material_file_type !== 'link' && Storage::disk('public')->exists($item->material_file_url)) {
            Storage::disk('public')->delete($item->material_file_url);
        }

        $item->delete();

        return $this->ok('Section item deleted successfully.');
    }
}
