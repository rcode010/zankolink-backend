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
        $this->authorize('viewAny', [SectionItem::class, $section]);

        $items = $section->items()->latest()->get();

        return $this->ok(
            'Section items retrieved successfully',
            SectionItemResource::collection($items)->resolve()
        );
    }

    /**
     * Add a section material.
     *
     * Creates a new section item. A material may contain:
     * - Title only
     * - Title and description
     * - A file attachment
     * - An external URL
     *
     * Only the title is required.
     *
     * @group Course Sections
     *
     * @authenticated
     *
     * @urlParam section integer required The ID of the course section. Example: 5
     *
     * @bodyParam title string required The material title. Example: Week 1 Slides
     * @bodyParam description string Optional description for the material. Example: Slides covering the first lecture.
     * @bodyParam file file Optional file to upload.
     * @bodyParam url string Optional external URL. Example: https://example.com/slides
     * @bodyParam material_file_name string Optional custom display name for the file or link. Example: Lecture Slides
     *
     * @response 201 {
     * "success": true,
     * "message": "Material added successfully.",
     * "data": {
     * "id": 16,
     * "section_id": 1,
     * "title": "title",
     * "description": "this is the description",
     * "type": "note",
     * "material_file_type": null,
     * "material_file_name": null,
     * "material_file_url": null,
     * "created_at": "2026-07-14 15:01:42",
     * "updated_at": "2026-07-14 15:01:42"
     * }
     * }
     */
    public function store(StoreSectionItemRequest $request, CourseSection $section)
    {
        $teacher = $request->user()->teacher;
        $this->authorize('create', [SectionItem::class, $section]);
        $validated = $request->validated();

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'created_by_teacher_id' => $teacher->id,
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
            $data['material_file_name'] = $validated['material_file_name']
                ?? parse_url($validated['url'], PHP_URL_HOST);
            $data['material_file_url'] = $validated['url'];
        }

        $item = $section->items()->create($data);

        return $this->success(
            'Material added successfully.',
            (new SectionItemResource($item))->resolve(),
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
        $this->authorize('view', $item);
        return $this->ok(
            'Section item retrieved successfully',
            (new SectionItemResource($item))->resolve()
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
        $this->authorize('download', $item);

        if ($item->material_file_type === 'link' || Str::startsWith($item->material_file_url, ['http://', 'https://'])) {
            return redirect()->away($item->material_file_url);
        }

        if (! Storage::disk('public')->exists($item->material_file_url)) {
            return $this->error('File not found.', 404);
        }

        return Storage::disk('public')->download($item->material_file_url, $item->material_file_name);
    }

    /**
     * Update a section material.
     *
     * Updates a section item's title, description, or attached material.
     *
     * You may:
     * - Replace the existing file.
     * - Replace the existing URL.
     * - Remove the existing material.
     * - Update only the title or description.
     *
     * @group Course Sections
     *
     * @authenticated
     *
     * @urlParam section integer required The ID of the course section. Example: 5
     * @urlParam item integer required The ID of the section item. Example: 12
     *
     * @bodyParam title string The material title. Example: Updated Week 1 Slides
     * @bodyParam description string Optional description. Example: Updated lecture notes.
     * @bodyParam file file Optional replacement file.
     * @bodyParam url string Optional replacement URL. Example: https://example.com/new-slides
     * @bodyParam material_file_name string Optional custom display name. Example: Updated Slides
     * @bodyParam remove_material boolean Remove the existing file or URL. Cannot be used together with file or url. Example: true
     *
     * @response {
  "success": true,
  "message": "Section item updated successfully.",
  "data": {
    "id": 16,
    "section_id": 1,
    "title": "new title",
    "description": "new description",
    "type": "link",
    "material_file_type": "link",
    "material_file_name": "hilo",
    "material_file_url": "http://google.com",
    "created_at": "2026-07-14 15:01:42",
    "updated_at": "2026-07-14 15:47:36"
  }
}
     */
    public function update(UpdateSectionItemRequest $request, SectionItem $item)
    {
        $this->authorize('update', $item);
        $validated = $request->validated();

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ];

        if ($request->hasFile('file'))
        {
            if ($item->material_file_type !== 'link' && $item->material_file_url)
            {
                Storage::disk('public')->delete($item->material_file_url);
            }

            $file = $request->file('file');
            $path = $file->store('section-items', 'public');

            $data['material_file_type'] = $file->getClientOriginalExtension();
            $data['material_file_name'] = $validated['material_file_name'] ?? $file->getClientOriginalName();
            $data['material_file_url'] = $path;

        } elseif (!empty($validated['url']))
        {
            if ($item->material_file_type !== 'link' && $item->material_file_url) {
                Storage::disk('public')->delete($item->material_file_url);
            }

            $data['material_file_type'] = 'link';
            $data['material_file_name'] = $validated['material_file_name'];
            $data['material_file_url'] = $validated['url'];

        } elseif (!empty($validated['remove_material']))
        {
            if ($item->material_file_type !== 'link' && $item->material_file_url) {
                Storage::disk('public')->delete($item->material_file_url);
            }

            $data['material_file_type'] = null;
            $data['material_file_name'] = null;
            $data['material_file_url'] = null;
        }

        $item->update($data);

        return $this->ok(
            'Section item updated successfully.',
            (new SectionItemResource($item->fresh()))->resolve()
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
        $this->authorize('delete', $item);
        if ($item->material_file_type !== 'link' &&
            !is_null($item->material_file_url) &&
            Storage::disk('public')->exists($item->material_file_url)) {
            Storage::disk('public')->delete($item->material_file_url);
        }

        $item->delete();

        return $this->ok('Section item deleted successfully.');
    }
}
