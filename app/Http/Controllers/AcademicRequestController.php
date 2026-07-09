<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicRequest;
use App\Http\Resources\AcademicRequestResource;
use App\Models\AcademicRequest;
use App\Services\AcademicRequestDepartmentResolver;
use App\Traits\ApiResponses;
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
        $academicRequest = DB::transaction(function () use ($departmentId, $credentials, $user,$request) {

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
            return $academicRequest->fresh()->load(['attachments','user','department']);

        });

        return $this->created('Academic request created successfully', (new AcademicRequestResource($academicRequest))->resolve());
    }
}
