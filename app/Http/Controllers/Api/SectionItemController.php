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

class SectionItemController extends Controller
{
    use ApiResponses;

    /**
     * GET /api/course-sections/{section}/items
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
     * POST /api/course-sections/{section}/items
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
     * GET /api/section-items/{item}
     */
    public function show(Request $request, SectionItem $item)
    {
        return $this->ok(
            'Section item retrieved successfully',
            (new SectionItemResource($item))->toArray($request)
        );
    }

    /**
     * GET /api/section-items/{item}/download
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
     * PUT/PATCH /api/section-items/{item}
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
     * DELETE /api/section-items/{item}
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