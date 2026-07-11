<?php

namespace App\Policies;

use App\Models\SectionSubmission;
use App\Models\SectionSubmissionAttachment;
use App\Models\User;

class SectionSubmissionAttachmentPolicy
{
    public function download(User $user, SectionSubmissionAttachment $attachment): bool
    {
        return $this->ownsSubmission($user, $attachment->submission);
    }

    public function delete(User $user, SectionSubmissionAttachment $attachment): bool
    {
        return $this->ownsSubmission($user, $attachment->submission);
    }

    private function ownsSubmission(User $user, SectionSubmission $submission): bool
    {
        return $user->teacher
            && $submission->section->teacher_id === $user->teacher->id;
    }
}
