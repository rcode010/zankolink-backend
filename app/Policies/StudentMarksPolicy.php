<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseAssessments;
use App\Models\StudentMarks;
use App\Models\User;

class StudentMarksPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, CourseAssessments $courseAssessments): bool
    {
        return $this->teacherOwnsAssessment($user, $courseAssessments)
            || $this->teacherIsPrimaryLecturer($user, $courseAssessments->course);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StudentMarks $studentMarks): bool
    {
        $assessment = $studentMarks->courseAssessment;

        return $this->teacherOwnsAssessment($user, $assessment)
            || $this->teacherIsPrimaryLecturer($user, $assessment->course);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, CourseAssessments $courseAssessments): bool
    {
        return $this->teacherOwnsAssessment($user, $courseAssessments)
            || $this->teacherIsPrimaryLecturer($user, $courseAssessments->course);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StudentMarks $studentMarks): bool
    {
        $assessment = $studentMarks->courseAssessment;

        return $this->teacherOwnsAssessment($user, $assessment)
            || $this->teacherIsPrimaryLecturer($user, $assessment->course);
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

    public function viewGradeBook(User $user, Course $course): bool
    {
        return $this->teacherIsPrimaryLecturer($user, $course)
            || $this->isHeadOfDepartment($user, $course);
    }

    public function storeGradeBook(User $user, Course $course): bool
    {
        return $this->teacherIsPrimaryLecturer($user, $course);
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

    private function teacherIsPrimaryLecturer(User $user, Course $course)
    {
        return $user->teacher &&
            $course->teachers()
                ->where('teachers.id', $user->teacher->id)
                ->wherePivot('role', 'primary_lecturer')
                ->exists();
    }

    private function isHeadOfDepartment(User $user, Course $course): bool
    {
        return $user->hasRole('HEAD_OF_DEPARTMENT')
            &&
            $user->userScopes()

                ->where('scope_type', 'DEPARTMENT')
                ->where('scope_id', $course->department_id)
                ->exists();
    }
}
