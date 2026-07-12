<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseAttendanceSessions;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CourseAttendanceSessionsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; //will fix after permissions are added
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CourseAttendanceSessions $courseAttendanceSessions): bool
    {
        return $this->ownsSession($user, $courseAttendanceSessions);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Course $course): bool
    {
        return $user->teacher &&
            $course->teachers()
                ->whereKey($user->teacher->id)
                ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CourseAttendanceSessions $courseAttendanceSessions): bool
    {
        return $this->ownsSession($user, $courseAttendanceSessions);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CourseAttendanceSessions $courseAttendanceSessions): bool
    {
        return $this->ownsSession($user, $courseAttendanceSessions);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CourseAttendanceSessions $courseAttendanceSessions): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CourseAttendanceSessions $courseAttendanceSessions): bool
    {
        return false;
    }

    private function ownsSession(User $user, CourseAttendanceSessions $courseAttendanceSession): bool
    {
        return $user->teacher
            && $courseAttendanceSession->teacher_id === $user->teacher->id;
    }
}
