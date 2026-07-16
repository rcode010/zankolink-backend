<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Letter;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
/**
 * @group Attachement
 *
 * APIs for attachement CRUD.
 */
class AttachmentController extends Controller
{
    use ApiResponses;

    /**
     * Download attachment.
     */
    public function download(Letter $letter, Attachment $attachment)
    {
        if ($attachment->letter_id !== $letter->id) {
            return $this->error(
                'Attachment not found.',
                404
            );
        }

        if (! Storage::disk('public')->exists($attachment->file_url)) {
            return $this->error(
                'File not found.',
                404
            );
        }

        return Storage::disk('public')->download(
            $attachment->file_url,
            $attachment->file_name
        );
    }

    /**
     * Delete attachment.
     */
    public function destroy(
        Letter $letter,
        Attachment $attachment
    ): JsonResponse {
        if ($attachment->letter_id !== $letter->id) {
            return $this->error(
                'Attachment not found.',
                404
            );
        }

        if (Storage::disk('public')->exists($attachment->file_url)) {
            Storage::disk('public')->delete(
                $attachment->file_url
            );
        }

        $attachment->delete();

        return $this->deleted(
            'Attachment deleted successfully.'
        );
    }
}
