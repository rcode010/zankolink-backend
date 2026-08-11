<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponses;

use App\Models\HighSchoolStudent;
use Illuminate\Http\Request;

class HighSchoolStudentsController extends Controller
{

    use ApiResponses;

    public function index()
    {
        return HighSchoolStudent::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return HighSchoolStudent::create($data);
    }

    public function show(HighSchoolStudent $highSchoolStudents)
    {
        return $highSchoolStudents;
    }

    public function update(Request $request, HighSchoolStudent $highSchoolStudents)
    {
        $data = $request->validate([

        ]);

        $highSchoolStudents->update($data);

        return $highSchoolStudents;
    }

    public function destroy(HighSchoolStudent $highSchoolStudents)
    {
        $highSchoolStudents->delete();

        return response()->json();
    }

    public function summary(Request $request)
    {
        $search = $request->query('search');

        if (! $search) {
            return $this->ok("Student summary returned successfully", []);
        }

        $student = HighSchoolStudent::withCount('choices')
            ->addSelect(['id', 'name', 'code', 'grade_average', 'status'])
            ->where('code', $search)
            ->first();

        return $this->ok("Student summary returned successfully", $student->toArray());
    }

    public function zankolineAnalytics()
    {
        $stats = HighSchoolStudent::selectRaw(
            'COUNT(*) as total,
         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as submitted,
         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft'
        )->first();

        return $this->ok("Zankoline stats returned successfully", [
            'total' => (int)$stats->total,
            'submitted' => (int)$stats->submitted,
            'draft' => (int)$stats->draft,
        ]);
    }
}
