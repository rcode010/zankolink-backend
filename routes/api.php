<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

require __DIR__.'/api/auth.php';
require __DIR__.'/api/letter-verification.php';

Route::middleware(['auth:sanctum', 'throttle:api', 'active'])->group(function () {

    Route::middleware('ability:moodle,admin,zankoline')->group(function () {
        require __DIR__.'/api/general.php';
    });
    Route::middleware('ability:moodle,admin')->group(function () {
        require __DIR__.'/api/shared.php';
    });
    Route::middleware('ability:admin')->group(function () {
        require __DIR__.'/api/universities.php';
        require __DIR__.'/api/academic-years.php';
        require __DIR__.'/api/faculties.php';
        require __DIR__.'/api/departments.php';
        require __DIR__.'/api/teachers.php';
        require __DIR__.'/api/students.php';
        require __DIR__.'/api/courses.php';
        require __DIR__.'/api/dashboard.php';

        require __DIR__.'/api/teacher-departments.php';
        require __DIR__.'/api/course-teachers.php';
        require __DIR__.'/api/course-students.php';

        require __DIR__.'/api/letters.php';
        require __DIR__.'/api/workflows.php';
        require __DIR__.'/api/attachments.php';
        require __DIR__.'/api/signatures.php';
        require __DIR__.'/api/users.php';

        require __DIR__.'/api/roles_permissions.php';
        require __DIR__.'/api/letter-stamp.php';
        require __DIR__.'/api/letter-recipients.php';

        require __DIR__.'/api/department-offering-subjects.php';
    });

    Route::middleware('ability:moodle')->group(function () {
        require __DIR__.'/api/course-assessment.php';
        require __DIR__.'/api/student-marks.php';

        require __DIR__.'/api/course-sections.php';
        require __DIR__.'/api/section-submission.php';
        require __DIR__.'/api/student-submissions.php';
        require __DIR__.'/api/section_items.php';
        require __DIR__.'/api/moodle.php';
        require __DIR__.'/api/lecturer-moodle.php';
        require __DIR__.'/api/course-attendance-sessions.php';
        require __DIR__.'/api/student-attendance.php';
        require __DIR__.'/api/academic-request.php';
    });
    Route::middleware('ability:zankoline')->group(function () {
        require __DIR__.'/api/zankoline/student-contact-info.php';
        require __DIR__.'/api/zankoline/student-application.php';
        require __DIR__.'/api/zankoline/student-subjects.php';
    });
    require __DIR__.'/api/zankoline/department-offering.php';
});
