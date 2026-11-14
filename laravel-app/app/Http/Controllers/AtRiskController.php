<?php

namespace App\Http\Controllers;

use App\Models\StudentIntervention;
use App\Models\StudentRecord;
use App\Services\AuditLogger;
use App\Services\StudentRiskAnalysis;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AtRiskController extends Controller
{
    public function index(Request $request, StudentRiskAnalysis $analysis)
    {
        $cohort = $analysis->cohort();
        $students = collect($cohort['students']);

        if ($search = trim((string) $request->query('q', ''))) {
            $students = $students->filter(function (array $row) use ($search) {
                return str_contains(strtolower($row['student_id']), strtolower($search));
            });
        }
        if ($request->filled('level')) {
            $students = $students->where('level', $request->query('level'));
        }
        if ($request->filled('year_level')) {
            $students = $students->where('year_level', $request->integer('year_level'));
        }
        if ($request->filled('program_id')) {
            $students = $students->where('program_id', $request->integer('program_id'));
        }

        $programs = $students->isEmpty()
            ? collect($cohort['students'])->pluck('program', 'program_id')->filter()
            : collect($cohort['students'])->pluck('program', 'program_id')->filter();

        return view('at-risk.index', [
            'summary' => $cohort['summary'],
            'students' => $students->values(),
            'programs' => $programs,
        ]);
    }

    public function show(StudentRecord $student, StudentRiskAnalysis $analysis)
    {
        $student->load(['program.department.college', 'academicYear', 'semester']);

        return view('at-risk.show', [
            'student' => $student,
            'analysis' => $analysis->forStudent($student),
            'interventions' => StudentIntervention::query()
                ->with('recorder')
                ->where('student_id', $student->student_id)
                ->latest('provided_on')
                ->get(),
        ]);
    }

    public function print(StudentRecord $student, StudentRiskAnalysis $analysis)
    {
        $student->load(['program.department.college', 'academicYear', 'semester']);

        return view('at-risk.print', [
            'student' => $student,
            'analysis' => $analysis->forStudent($student),
            'interventions' => StudentIntervention::query()
                ->where('student_id', $student->student_id)
                ->latest('provided_on')
                ->get(),
        ]);
    }

    public function storeIntervention(Request $request, StudentRecord $student, AuditLogger $audit)
    {
        $data = $request->validate([
            'intervention_type' => ['required', Rule::in(array_keys(StudentIntervention::TYPES))],
            'provided_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        StudentIntervention::query()->create($data + [
            'student_id' => $student->student_id,
            'student_record_id' => $student->id,
            'outcome' => 'pending',
            'gpa_at_record' => $student->gpa,
            'attendance_at_record' => $student->attendance_percent,
            'recorded_by' => $request->user()->id,
        ]);

        $audit->log('create', 'at-risk', 'Recorded an intervention for '.$student->student_id.'.');

        return redirect()->route('at-risk.show', $student)->with('success', 'Intervention recorded. Compare the next term with the GPA and attendance saved on this entry.');
    }

    public function updateIntervention(Request $request, StudentIntervention $intervention, AuditLogger $audit)
    {
        $data = $request->validate([
            'outcome' => ['required', Rule::in(array_keys(StudentIntervention::OUTCOMES))],
        ]);

        $intervention->update($data);
        $audit->log('update', 'at-risk', 'Updated intervention outcome for '.$intervention->student_id.' to '.$intervention->outcomeLabel().'.');

        return back()->with('success', 'Intervention outcome updated.');
    }

    public function warnings(StudentRiskAnalysis $analysis, AuditLogger $audit)
    {
        $created = $analysis->raiseWarnings();
        $audit->log('create', 'at-risk', 'Checked at-risk warning thresholds. New alerts: '.$created.'.');

        return redirect()
            ->route('at-risk.index')
            ->with('success', $created === 0
                ? 'No new warning alerts. Students already notified in the last 14 days were skipped.'
                : $created.' warning alert'.($created === 1 ? '' : 's').' sent to the Alerts list.');
    }
}
