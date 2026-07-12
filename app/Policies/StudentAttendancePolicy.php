<?php

namespace App\Policies;

use App\Models\CourseAttendanceSessions;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentAttendancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, CourseAttendanceSessions $courseAttendanceSessions): bool
    {
        return $this->ownsSession($user, $courseAttendanceSessions);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StudentAttendance $studentAttendance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, CourseAttendanceSessions $courseAttendanceSessions): bool
    {
        return $this->ownsSession($user, $courseAttendanceSessions);
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
    public function delete(User $user, StudentAttendance $studentAttendance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StudentAttendance $studentAttendance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StudentAttendance $studentAttendance): bool
    {
        return false;
    }

    public function viewOwn(User $user): bool
    {
        return $user->student !== null;
    }

    private function ownsSession(User $user, CourseAttendanceSessions $courseAttendanceSession): bool
    {
        return $user->teacher
            && $courseAttendanceSession->teacher_id === $user->teacher->id;
    }
}
