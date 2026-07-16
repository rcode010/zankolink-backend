<?php
namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CourseSection;
use App\Models\SectionSubmission;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class SectionSubmissionService
{

    public function create(
        CourseSection $section,
        array $data,
        array $files = [],
        Teacher $teacher
    ): SectionSubmission
    {

        return DB::transaction(function () use ($section, $data, $files, $teacher) {

            $assessment = $section->course->assessments()->create([
                'academic_year_id' => AcademicYear::where('is_active', true)->value('id'),
                'title' => $data['title'],
                'max_mark' => $data['max_mark'],
                'weight' => $data['weight'],
                'due_at' => $data['due_at'],
                'teacher_id' => $teacher->id,
                'course_id' => $section->course->id,
            ]);

            $submission = $section->submissions()->create([
                'course_assessment_id' => $assessment->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'due_at' => $data['due_at'] ?? null,
            ]);

            foreach ($files as $file) {
                $path = $file->store('section-submission', 'public');

                $submission->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'file_url' => $path,
                ]);
            }

            return $submission->load([
                'section:id,title',
                'attachments',
                'courseAssessment',
            ]);
        });
    }


    public function update(SectionSubmission $submission, array $data, array $files = []): SectionSubmission
    {
        return DB::transaction(function () use ($submission, $data, $files) {

            $submission->courseAssessment->update([
                'title' => $data['title'],
                'max_mark' => $data['max_mark'],
                'weight' => $data['weight'],
                'due_at' => $data['due_at'],
            ]);

            $submission->update([
                'description' => $data['description'],
            ]);

            return $submission->load([
                'section:id,title',
                'attachments',
                'courseAssessment',
            ]);
        });
    }

}
