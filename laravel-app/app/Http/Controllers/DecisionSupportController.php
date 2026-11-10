<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Services\AnalyticsService;
use App\Services\AuditLogger;
use App\Services\InsightService;
use App\Services\ScenarioService;
use Illuminate\Http\Request;

class DecisionSupportController extends Controller
{
    public function index()
    {
        $insights = AiInsight::query()->with('program')->latest()->paginate(12);

        return view('ai-support.index', compact('insights'));
    }

    public function generate(InsightService $insights, AuditLogger $audit)
    {
        $created = $insights->generate();
        $audit->log('generate_insights', 'ai', count($created).' insights generated from verified analytics.');

        return redirect()->route('ai-support.index')->with('success', count($created).' insights generated. Review them before acting. The system does not make administrative decisions.');
    }

    public function show(AiInsight $insight)
    {
        $insight->load('program');

        return view('ai-support.show', compact('insight'));
    }

    public function review(Request $request, AiInsight $insight, AuditLogger $audit)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending_review,accepted,rejected'],
        ]);
        $insight->update($validated);
        $audit->log('review_insight', 'ai', "Insight #{$insight->id} marked {$validated['status']}.");

        return back()->with('success', 'Insight review status updated.');
    }

    public function whatIf(Request $request, AnalyticsService $analytics, ScenarioService $scenarios)
    {
        $data = $analytics->dashboard();
        $defaults = [
            'recruitment_pct' => 10,
            'retention_pct' => 5,
            'capacity' => 5500,
        ];

        $result = null;
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'recruitment_pct' => ['required', 'numeric', 'min:-50', 'max:100'],
                'retention_pct' => ['required', 'numeric', 'min:-50', 'max:100'],
                'capacity' => ['required', 'integer', 'min:1'],
            ]);
            $defaults = $validated;
            $result = $scenarios->run(
                $data['totalEnrollment'],
                $data['newStudents'],
                $data['returningStudents'],
                (float) $validated['recruitment_pct'],
                (float) $validated['retention_pct'],
                (int) $validated['capacity'],
            );
        }

        return view('ai-support.what-if', compact('data', 'defaults', 'result'));
    }
}
