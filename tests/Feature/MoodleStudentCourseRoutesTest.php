<?php

use App\Http\Resources\SectionSubmissionResource;
use App\Models\SectionSubmission;
use App\Models\Student;
use App\Models\StudentSubmission;
use App\Models\User;

it('exposes the Moodle student course endpoints without a controller class redeclare', function () {
    $this->getJson('/api/moodle/my-courses')->assertUnauthorized();
    $this->getJson('/api/moodle/my-courses/1')->assertUnauthorized();
    $this->getJson('/api/moodle/my-courses/1/sections')->assertUnauthorized();
});

it('includes grade and feedback in the section submission response payload', function () {
    $student = new Student(['id' => 1]);
    $student->setRelation('user', new User(['id' => 1, 'name' => 'Test Student']));

    $submission = new SectionSubmission([
        'id' => 1,
        'course_section_id' => 1,
        'title' => 'Homework 1',
        'description' => 'Complete exercises 1-5',
        'deadline' => now()->addDay(),
        'weight' => 10,
    ]);

    $studentSubmission = new StudentSubmission([
        'id' => 1,
        'submission_id' => 1,
        'student_id' => 1,
        'file_name' => 'homework1.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 2048,
        'file_url' => 'homework1.pdf',
        'grade' => 88.5,
        'feedback' => 'Strong work overall.',
        'graded_at' => now(),
    ]);
    $studentSubmission->setRelation('student', $student);
    $studentSubmission->setRelation('submission', $submission);

    $submission->setRelation('studentSubmissions', collect([$studentSubmission]));

    $payload = (new SectionSubmissionResource($submission))->resolve();

    expect($payload)
        ->toHaveKey('grade', 88.5)
        ->toHaveKey('feedback', 'Strong work overall.')
        ->toHaveKey('graded_at');
});
