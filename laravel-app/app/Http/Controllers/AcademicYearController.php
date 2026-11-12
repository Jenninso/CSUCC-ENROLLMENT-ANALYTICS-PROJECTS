<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\EnrollmentRecord;
use App\Models\StudentRecord;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::orderByDesc('start_date')->paginate(15);

        return view('data-management.academic-years.index', compact('academicYears'));
    }

    public function create()
    {
        return view('data-management.academic-years.create');
    }

    public function store(Request $request, AuditLogger $audit)
    {
        AcademicYear::create($this->validated($request));
        $audit->log('create', 'academic_years', $request->academic_year);

        return redirect()->route('data-management.academic-years.index')->with('success', 'Academic year created.');
    }

    public function edit(AcademicYear $academicYear)
    {
        return view('data-management.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear, AuditLogger $audit)
    {
        $academicYear->update($this->validated($request, $academicYear->id));
        $audit->log('update', 'academic_years', $academicYear->academic_year);

        return redirect()->route('data-management.academic-years.index')->with('success', 'Academic year updated.');
    }

    public function destroy(AcademicYear $academicYear, AuditLogger $audit)
    {
        $hasEnrollment = EnrollmentRecord::query()->where('academic_year_id', $academicYear->id)->exists();
        $hasStudents = StudentRecord::query()->where('academic_year_id', $academicYear->id)->exists();
        if ($hasEnrollment || $hasStudents) {
            return back()->with('error', 'This academic year has enrollment or student records and cannot be deleted.');
        }

        try {
            DB::transaction(function () use ($academicYear) {
                $academicYear->semesters()->delete();
                $academicYear->delete();
            });
        } catch (QueryException) {
            return back()->with('error', 'This academic year is still used by other records and cannot be deleted.');
        }
        $audit->log('delete', 'academic_years', $academicYear->academic_year);

        return redirect()->route('data-management.academic-years.index')->with('success', 'Academic year deleted.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'academic_year' => ['required', 'string', 'max:20', Rule::unique('academic_years', 'academic_year')->ignore($id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
