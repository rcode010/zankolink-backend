<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\SectionSubmission;
use App\Models\User;

class SectionSubmissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, CourseSection $courseSection): bool
    {
        return $this->teacherBelongsToCourse($user, $courseSection->course)
            || $this->studentBelongsToCourse($user, $courseSection->course);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SectionSubmission $sectionSubmission): bool
    {
        return $this->teacherBelongsToCourse($user, $sectionSubmission->section->course)
            || $this->studentBelongsToCourse($user, $sectionSubmission->section->course);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, CourseSection $courseSection): bool
    {
        return $this->teacherBelongsToCourse($user, $courseSection->course);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SectionSubmission $sectionSubmission): bool
    {
        return $this->is_primary($user, $sectionSubmission->section->course) || $this->ownsSubmission($user, $sectionSubmission);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SectionSubmission $sectionSubmission): bool
    {
        return $this->is_primary($user, $sectionSubmission->section->course) || $this->ownsSubmission($user, $sectionSubmission);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SectionSubmission $sectionSubmission): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SectionSubmission $sectionSubmission): bool
    {
        return false;
    }

    private function teacherBelongsToCourse(User $user, Course $course): bool
    {
        return $user->teacher
            && $course->teachers()
                ->whereKey($user->teacher->id)
                ->exists();
    }

    private function studentBelongsToCourse(User $user, Course $course): bool
    {
        return $user->student
            && $course->students()
                ->whereKey($user->student->id)
                ->exists();
    }

    private function ownsSection(User $user, CourseSection $courseSection): bool
    {
        return $user->teacher
            && $courseSection->teacher_id === $user->teacher->id;
    }

    private function ownsSubmission(User $user, SectionSubmission $sectionSubmission): bool
    {
        return $user->teacher &&
            $sectionSubmission->created_by_teacher_id === $user->teacher->id;
    }

    private function is_primary(User $user, Course $course): bool
    {
        return $user->teacher &&
            $course->teachers()->whereKey($user->teacher->id)->where('role', 'primary_lecturer')->exists();
    }
}
