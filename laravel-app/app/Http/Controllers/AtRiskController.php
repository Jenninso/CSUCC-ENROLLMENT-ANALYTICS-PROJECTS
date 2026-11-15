<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\StudentIntervention;
use App\Models\StudentRecord;
use App\Services\AuditLogger;
use App\Services\StudentAttritionService;
use App\Services\StudentRiskAnalysis;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AtRiskController extends Controller
{
    public function index(Request $request, StudentRiskAnalysis $analysis)
    {
        $listing = $analysis->browse($request);

        return view('at-risk.index', [
            'summary' => $listing['summary'],
            'students' => $listing['students'],
            'programs' => $listing['programs'],
        ]);
    }

    public function attritionPage()
    {
        return view('at-risk.attrition', [
            'attrition' => session('attrition'),
        ]);
    }

    public function attritionStudent(string $studentId)
    {
        $result = session('attrition');
        $row = collect($result['students'] ?? [])->firstWhere('student_id', $studentId);
        if (! is_array($row)) {
            return redirect()
                ->route('at-risk.attrition.show')
                ->with('error', 'That student is not in the latest attrition list. Run the student model again.');
        }

        $record = StudentRecord::query()
            ->where('student_id', $studentId)
            ->latest('id')
            ->first();

        return view('at-risk.attrition-student', [
            'row' => $row,
            'record' => $record,
        ]);
    }

    public function attrition(StudentAttritionService $attrition)
    {
        $result = $attrition->predict();
        session(['attrition' => $result]);
        $created = ($result['ok'] ?? false) ? $this->storeAttritionAlerts($result['students'] ?? []) : 0;

        return redirect()
            ->route('at-risk.attrition.show')
            ->with('success', $created === 0
                ? 'Student model finished. No new attrition alerts were added.'
                : $created.' attrition alert'.($created === 1 ? '' : 's').' sent to AI Alerts.');
    }

    /**
     * @param  list<array<string, mixed>>  $students
     */
    private function storeAttritionAlerts(array $students): int
    {
        $recent = array_fill_keys(
            Alert::query()
                ->where('metric', 'student_attrition')
                ->where('created_at', '>=', now()->subDays(14))
                ->pluck('title')
                ->all(),
            true
        );
        $now = now();
        $batch = [];
        foreach ($students as $row) {
            $title = 'Attrition estimate: '.$row['student_id'];
            if (isset($recent[$title])) {
                continue;
            }
            $batch[] = [
                'title' => $title,
                'program_id' => null,
                'metric' => 'student_attrition',
                'current_value' => $row['chance'],
                'expected_value' => 50,
                'severity' => ($row['chance'] ?? 0) >= 75 ? 'high' : 'medium',
                'explanation' => $row['student_id'].' has a '.$row['chance'].'% attrition estimate. '.($row['suggestion'] ?? ''),
                'recommended_action' => $row['suggestion'] ?? 'Open the attrition page and review this student.',
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($batch !== []) {
            Alert::query()->insert($batch);
        }

        return count($batch);
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
