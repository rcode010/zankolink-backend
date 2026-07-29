<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicRequestUpdateRequest;
use App\Http\Requests\StoreAcademicRequest;
use App\Http\Resources\AcademicRequestResource;
use App\Http\Resources\DepartmentAcademicRequestResource;
use App\Models\AcademicRequest;
use App\Services\AcademicRequestDepartmentResolver;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Academic Requests
 *
 * Endpoints for managing academic requests submitted by students and teachers.
 */
class AcademicRequestController extends Controller
{
    use ApiResponses;

    /**
     * List Academic Requests
     *
     * Returns a paginated list of academic requests submitted by the authenticated user.
     *
     * @authenticated
     *
     * @queryParam per_page integer optional Number of results per page. Defaults to 15. Example: 10
     *
     * @response 200 scenario="Requests retrieved successfully" {
     *   "success": true,
     *   "message": "Academic Request retrieved successfully",
     *   "data": [
     *     {
     *       "id": 19,
     *       "type": "leave",
     *       "subject": "Medical Leave Request",
     *       "description": "I need a leave of absence due to medical reasons.",
     *       "status": "pending",
     *       "user": {
     *         "id": 479,
     *         "name": "Demo Teacher"
     *       },
     *       "department": {
     *         "id": 1,
     *         "name": "Nursing and Midwifery"
     *       },
     *       "attachments": [
     *         {
     *           "id": 37,
     *           "file_name": "medical_report.pdf",
     *           "file_type": "image/jpeg",
     *           "file_size": 259558,
     *           "file_url": "http://localhost:8000/storage/attachments/academic-requests/Fc8XE05sD8fRTZu6kuJZoJV5qzMj4PHRXlAxzWTL.jpg"
     *         }
     *       ],
     *       "created_at": "2026-07-09T08:06:22.000000Z",
     *       "updated_at": "2026-07-09T08:06:22.000000Z"
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $academicRequests = AcademicRequest::where(
            'user_id', $user->id,
        )->with(['attachments', 'user', 'department'])->get();

        return $this->ok('Academic Request retrieved successfully',
            (AcademicRequestResource::collection($academicRequests))->resolve());
    }

    public function update(AcademicRequestUpdateRequest $request, AcademicRequest $academicRequest)
    {
        $credentials = $request->validated();

        $academicRequest->update($credentials);

        return $this->ok('Academic request updated successfully', (new AcademicRequestResource($academicRequest))->resolve());
    }

    /**
     * Submit an Academic Request
     *
     * Allows a student or teacher to submit an academic request to their department.
     * Students have their department resolved automatically from their profile.
     * Teachers must provide a `department_id` they are assigned to.
     *
     * @authenticated
     *
     * @bodyParam type string required The type of academic request. Example: leave
     * @bodyParam subject string required The subject of the request. Example: Medical Leave Request
     * @bodyParam description string required A detailed description of the request. Example: I need a leave of absence due to medical reasons.
     * @bodyParam department_id integer nullable Required for teachers only. Must be a department the teacher is assigned to. Example: 3
     * @bodyParam files file[] optional Optional attachments. Accepted formats: pdf, doc, docx, jpg, jpeg, png. Max size: 5MB each.
     *
     * @response 201 scenario="Request created successfully" {
     *   "success": true,
     *   "message": "Academic request created successfully",
     *   "data": {
     *     "id": 11,
     *     "type": "leave",
     *     "subject": "Medical Leave Request",
     *     "description": "I need a leave of absence due to medical reasons.",
     *     "status": "pending",
     *     "user": {
     *       "id": 5,
     *       "name": "Albert Raman"
     *     },
     *     "department": {
     *       "id": 1,
     *       "name": "Computer Science"
     *     },
     *     "attachments": [
     *       {
     *         "id": 1,
     *         "file_name": "medical_report.pdf",
     *         "file_type": "application/pdf",
     *         "file_size": 204800,
     *         "file_url": "/storage/attachments/academic-requests/mGuJZ4StlE8RExYPqnNL2aHENTfYH8bv6kASm6EL.pdf"
     *       }
     *     ],
     *     "created_at": "2026-07-09T07:49:08.000000Z",
     *     "updated_at": "2026-07-09T07:49:08.000000Z"
     *   }
     * }
     */
    public function store(StoreAcademicRequest $request, AcademicRequestDepartmentResolver $departmentResolver)
    {
        $credentials = $request->validated();
        $user = $request->user();

        $departmentId = $departmentResolver->resolve(
            $user,
            $credentials['department_id'] ?? null
        );
        $academicRequest = DB::transaction(function () use ($departmentId, $credentials, $user, $request) {

            $academicRequest = AcademicRequest::create([
                'user_id' => $user->id,
                'department_id' => $departmentId,
                'subject' => $credentials['subject'],
                'description' => $credentials['description'],
                'status' => 'pending',
                'type' => $credentials['type'],
            ]);

            if ($request->hasFile('files')) {
                $attachments = [];
                foreach ($request->file('files') as $file) {
                    $path = $file->store('attachments/academic-requests', 'public');
                    $attachments[] = [
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'file_path' => $path,
                    ];
                }
                $academicRequest->attachments()->createMany($attachments);
            }

            return $academicRequest->fresh()->load(['attachments', 'user', 'department']);

        });

        return $this->created('Academic request created successfully', (new AcademicRequestResource($academicRequest))->resolve());
    }

    /**
     * Get Academic Request
     *
     * Returns a single academic request by ID belonging to the authenticated user.
     *
     * @authenticated
     *
     * @urlParam academicRequest integer required The ID of the academic request. Example: 1
     *
     * @response 200 scenario="Request retrieved successfully" {
     *   "success": true,
     *   "message": "Academic Request retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "type": "leave",
     *     "subject": "anything",
     *     "description": "asldfjasdl;kf",
     *     "status": "pending",
     *     "user": {
     *       "id": 480,
     *       "name": "Demo Student"
     *     },
     *     "department": {
     *       "id": 1,
     *       "name": "Nursing and Midwifery"
     *     },
     *     "attachments": [
     *       {
     *         "id": 1,
     *         "file_name": "wallhaven-o3qqy5.jpg",
     *         "file_type": "image/jpeg",
     *         "file_size": 259558,
     *         "file_url": "http://localhost:8000/storage/attachments/academic-requests/UzcNxzmNWLOvmJO7f3NuUyNux0IrjejReemfOAmG.jpg"
     *       },
     *       {
     *         "id": 2,
     *         "file_name": "wallhaven-qz1glr.jpg",
     *         "file_type": "image/jpeg",
     *         "file_size": 262923,
     *         "file_url": "http://localhost:8000/storage/attachments/academic-requests/fPI8nRT3hrqG4H7XPuZrng8XyOXjl5KmytXWZRQB.jpg"
     *       }
     *     ],
     *     "created_at": "2026-07-09T07:36:48.000000Z",
     *     "updated_at": "2026-07-09T07:36:48.000000Z"
     *   }
     * }
     * @response 404 scenario="Not found" {
     *   "message": "No query results for model [App\\Models\\AcademicRequest] 1"
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     */
    public function show(AcademicRequest $academicRequest)
    {
        return $this->ok('Academic Request retrieved successfully', (new AcademicRequestResource($academicRequest->load(['attachments', 'user', 'department'])))->resolve());
    }

    public function departmentAcademicRequests(Request $request)
    {
        $user = $request->user();
        $this->authorize('departmentAcademicRequest', AcademicRequest::class);

        $departmentId = $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->value('scope_id');

        $academicRequests = AcademicRequest::query()
            ->with([
                'user.teacher',
                'user.student',
                'department',
                'attachments',
            ])
            ->where('department_id', $departmentId)
            ->get();

        return $this->ok(
            'Academic requests retrieved successfully.',
            DepartmentAcademicRequestResource::collection($academicRequests)->resolve()
        );
    }
}
