<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\SectionItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SectionItemPolicy
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
    public function view(User $user, SectionItem $sectionItem): bool
    {
        return $this->teacherBelongsToCourse($user, $sectionItem->section->course)
            || $this->studentBelongsToCourse($user, $sectionItem->section->course);
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
    public function update(User $user, SectionItem $sectionItem): bool
    {
        return $this->is_primary($user,$sectionItem->section->course) || $this->ownsItem($user, $sectionItem);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SectionItem $sectionItem): bool
    {
        return $this->is_primary($user,$sectionItem->section->course)|| $this->ownsItem($user, $sectionItem);
    }

    public function download(User $user, SectionItem $sectionItem): bool
    {
        return $this->ownsSection($user, $sectionItem->section);
    }

    /**
     * Determine whether the user can restore the model.
     */

    public function restore(User $user, SectionItem $sectionItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SectionItem $sectionItem): bool
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
    private function ownsItem(User $user, SectionItem $sectionItem): bool{
        return $user->teacher &&
            $sectionItem->created_by_teacher_id === $user->teacher->id;
    }
    private function is_primary(User $user, Course $course): bool{
        return $user->teacher &&
            $course->teachers()->whereKey($user->teacher->id)->where('role','primary_lecturer')->exists();
    }
}
