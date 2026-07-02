<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SectionSubmissionAttachment;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Storage;

class SectionSubmissionAttachmentController extends Controller
{
    use ApiResponses;

    public function download(SectionSubmissionAttachment $attachment)
    {
        return Storage::disk('public')->download(
            $attachment->file_url,
            $attachment->file_name
        );
    }

    public function destroy(SectionSubmissionAttachment $attachment)
    {
        Storage::disk('public')->delete($attachment->file_url);

        $attachment->delete();

        return $this->success('Attachment deleted successfully.');
    }
}
