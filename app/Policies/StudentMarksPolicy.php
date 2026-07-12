<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseAssessments;
use App\Models\StudentMarks;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentMarksPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, CourseAssessments $courseAssessments): bool
    {
        return $this->teacherOwnsAssessment($user, $courseAssessments);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StudentMarks $studentMarks): bool
    {
        return $this->teacherOwnsAssessment($user, $studentMarks->courseAssessment);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, CourseAssessments $courseAssessments): bool
    {
        return $this->teacherOwnsAssessment($user, $courseAssessments);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StudentMarks $studentMarks): bool
    {
        return $this->teacherOwnsAssessment($user, $studentMarks->courseAssessment);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StudentMarks $studentMarks): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StudentMarks $studentMarks): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StudentMarks $studentMarks): bool
    {
        return false;
    }

    public function viewOwn(User $user, Course $course): bool
    {
        return $user->student &&
            $course->students()
                ->whereKey($user->student->id)
                ->exists();
    }

    private function teacherOwnsAssessment(User $user, CourseAssessments $assessment): bool
    {
        return $user->teacher &&
            $assessment->teacher_id === $user->teacher->id;
    }
}
