<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SemesterController extends Controller
{
    public function index()
    {
        $semesters = Semester::with('academicYear')->orderByDesc('academic_year_id')->orderBy('semester_order')->paginate(15);

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
        $semester->update($this->validated($request, $semester->id));
        $audit->log('update', 'semesters', $semester->semester_name);

        return redirect()->route('data-management.semesters.index')->with('success', 'Semester updated.');
    }

    public function destroy(Semester $semester, AuditLogger $audit)
    {
        try {
            $semester->delete();
        } catch (QueryException) {
            return back()->with('error', 'This semester is used by enrollment records and cannot be deleted.');
        }
        $audit->log('delete', 'semesters', $semester->semester_name);

        return redirect()->route('data-management.semesters.index')->with('success', 'Semester deleted.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('semesters', 'semester_name')
                    ->where(fn ($query) => $query->where('academic_year_id', $request->input('academic_year_id')))
                    ->ignore($id),
            ],
            'semester_order' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        $data['semester_order'] = $data['semester_order'] ?? 1;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
