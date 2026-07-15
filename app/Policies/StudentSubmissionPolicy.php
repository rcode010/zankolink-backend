<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\SectionSubmission;
use App\Models\StudentSubmission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentSubmissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, SectionSubmission $sectionSubmission): bool
    {
        return $this->ownsSectionSubmission($user, $sectionSubmission);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StudentSubmission $studentSubmission): bool
    {
        return $this->ownsSubmission($user, $studentSubmission)
            || $this->ownsSectionSubmission($user, $studentSubmission->submission);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, SectionSubmission $sectionSubmission): bool
    {
        return $this->studentBelongsToCourse($user, $sectionSubmission->section->course);
    }
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StudentSubmission $studentSubmission): bool
    {
        return $this->ownsSubmission($user, $studentSubmission);
    }

    public function download(User $user, StudentSubmission $studentSubmission): bool
    {
        return $this->ownsSubmission($user, $studentSubmission)
            || $this->ownsSectionSubmission($user, $studentSubmission->submission);
    }

    public function grade(User $user, StudentSubmission $studentSubmission): bool
    {
        return $this->ownsSectionSubmission($user, $studentSubmission->submission);
    }

    private function studentBelongsToCourse(User $user, Course $course): bool
    {
        return $user->student
            && $course->students()
                ->whereKey($user->student->id)
                ->exists();
    }

    private function ownsSubmission(User $user, StudentSubmission $studentSubmission): bool
    {
        return $user->student
            && $studentSubmission->student->id === $user->student->id;
    }

    private function ownsSectionSubmission(User $user, SectionSubmission $sectionSubmission): bool
    {
        return $user->teacher
            && $sectionSubmission->section->teacher_id === $user->teacher->id;
    }
}
