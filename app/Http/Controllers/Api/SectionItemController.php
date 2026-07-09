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
     * * Get all items (files/links) attached to a specific course section.
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
     * * Upload a physical file or attach an external link to a course section.
     * * @urlParam section int required The ID of the course section. Example: 1
     * @bodyParam file file The physical document or media file to upload (Max 50MB). Required if url is omitted.
     * @bodyParam url string The full external link/URL. Required if file is omitted. Example: https://example.com/slide.pdf
     * @bodyParam material_file_name string Custom display name for the material. Required only if url is provided. Example: Lecture 1 Slides
     */
    public function store(StoreSectionItemRequest $request, CourseSection $section)
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('section-items', 'public');

            $item = $section->items()->create([
                'material_file_type' => $file->getClientOriginalExtension(),
                'material_file_name' => $validated['material_file_name'] ?? $file->getClientOriginalName(),
                'material_file_url' => $path,
            ]);
        } else {
            $item = $section->items()->create([
                'material_file_type' => 'link',
                'material_file_name' => $validated['material_file_name'],
                'material_file_url' => $validated['url'],
            ]);
        }

        return $this->success(
            'Material added successfully.',
            (new SectionItemResource($item))->toArray($request),
            201
        );
    }

    /**
     * View Section Item
     * * Fetch details of a specific section item.
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
     * * Downloads the file directly if it's hosted locally, or redirects away if it's an external URL.
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
     * * Update metadata (like custom file name) for an item. Re-uploading a new file should go through delete + store instead.
     * * @urlParam item int required The ID of the section item. Example: 5
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
     * * Permanently remove an item and delete its physical file from storage if applicable.
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