<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function index()
    {
        $semesters = Semester::with('academicYear')->orderByDesc('id')->paginate(15);

        return view('data-management.semesters.index', compact('semesters'));
    }

    public function create()
    {
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        return view('data-management.semesters.create', compact('academicYears'));
    }

    public function store(Request $request, AuditLogger $audit)
    {
        Semester::create($this->validated($request));
        $audit->log('create', 'semesters', $request->semester_name);

        return redirect()->route('data-management.semesters.index')->with('success', 'Semester created.');
    }

    public function edit(Semester $semester)
    {
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        return view('data-management.semesters.edit', compact('semester', 'academicYears'));
    }

    public function update(Request $request, Semester $semester, AuditLogger $audit)
    {
        $semester->update($this->validated($request));
        $audit->log('update', 'semesters', $semester->semester_name);

        return redirect()->route('data-management.semesters.index')->with('success', 'Semester updated.');
    }

    public function destroy(Semester $semester, AuditLogger $audit)
    {
        $semester->delete();
        $audit->log('delete', 'semesters', $semester->semester_name);

        return redirect()->route('data-management.semesters.index')->with('success', 'Semester deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_name' => ['required', 'string', 'max:50'],
        ]);
    }
}
