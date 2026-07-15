<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseAssessments;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CourseAssessmentsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Course $course): bool
    {
        return $this->teacherBelongsToCourse($user, $course);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CourseAssessments $courseAssessments): bool
    {
        return $this->teacherOwnsAssessment($user, $courseAssessments);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Course $course): bool
    {
        return $this->teacherBelongsToCourse($user, $course);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CourseAssessments $courseAssessments): bool
    {
        return $this->teacherOwnsAssessment($user, $courseAssessments);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CourseAssessments $courseAssessments): bool
    {
        return $this->teacherOwnsAssessment($user, $courseAssessments);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CourseAssessments $courseAssessments): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CourseAssessments $courseAssessments): bool
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

    private function teacherOwnsAssessment(User $user, CourseAssessments $assessment): bool
    {
        return $user->teacher &&
            $assessment->teacher_id === $user->teacher->id;
    }
}
