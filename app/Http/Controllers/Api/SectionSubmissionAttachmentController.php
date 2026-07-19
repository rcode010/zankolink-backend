<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SectionSubmissionAttachment;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Storage;
/**
 * @group Section-Submission
 *
 * APIs for section-submission CRUD.
 */
class SectionSubmissionAttachmentController extends Controller
{
    use ApiResponses;

    /**
     * Delete assignment attachment
     *
     * Deletes a single attachment from an assignment.
     *
     * @authenticated
     *
     * @urlParam attachment integer required The ID of the attachment. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "message": "Attachment deleted successfully.",
     * "data": []
     * }
     */
    public function destroy(SectionSubmissionAttachment $attachment)
    {
        $this->authorize('delete', $attachment);
        Storage::disk('public')->delete($attachment->file_url);

        $attachment->delete();

        return $this->success('Attachment deleted successfully.');
    }
}
