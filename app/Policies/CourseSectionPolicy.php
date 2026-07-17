<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\User;

class CourseSectionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Course $course): bool
    {
        return $this->teacherBelongsToCourse($user, $course)
            || $this->studentBelongsToCourse($user, $course);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CourseSection $courseSection): bool
    {
        return $this->teacherBelongsToCourse($user, $courseSection->course)
            || $this->studentBelongsToCourse($user, $courseSection->course);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Course $course): bool
    {
        return $this->is_primary_lecturer($user, $course);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CourseSection $courseSection): bool
    {
        return $this->teacherBelongsToCourse($user, $courseSection->course);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CourseSection $courseSection): bool
    {
        return $this->teacherBelongsToCourse($user, $courseSection->course);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CourseSection $courseSection): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CourseSection $courseSection): bool
    {
        return false;
    }

    private function teacherBelongsToCourse(User $user, Course $course): bool
    {
        return $user->teacher &&
            $course->teachers()
                ->whereKey($user->teacher->id)
                ->exists();
    }

    private function studentBelongsToCourse(User $user, Course $course): bool
    {
        return $user->student &&
            $course->students()
                ->whereKey($user->student->id)
                ->exists();
    }

    private function is_primary_lecturer(User $user, Course $course): bool{
        if (! $user->teacher) {
            return false;
        }
        return $course->teachers()
                ->whereKey($user->teacher->id)
                ->where('role', 'primary_lecturer')
                ->exists();

    }
}
