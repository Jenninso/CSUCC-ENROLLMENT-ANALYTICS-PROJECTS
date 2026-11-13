<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\StudentRecord;
use App\Services\AlertService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $alerts = Alert::with('program')
            ->when($request->severity, fn ($q, $v) => $q->where('severity', $v))
            ->when($request->status === 'unread', fn ($q) => $q->where('is_read', false))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('alerts.index', compact('alerts'));
    }

    public function generate(AlertService $alerts, AuditLogger $audit)
    {
        $created = $alerts->evaluate();
        $audit->log('generate_alerts', 'alerts', count($created).' alerts evaluated.');

        return redirect()->route('alerts.index')->with('success', count($created).' alert(s) created from current thresholds.');
    }

    public function show(Alert $alert)
    {
        $alert->load('program');
        $student = null;
        if (preg_match('/At-risk warning:\s*(.+)$/i', (string) $alert->title, $match)) {
            $student = StudentRecord::query()
                ->where('student_id', trim($match[1]))
                ->latest('id')
                ->first();
        }

        return view('alerts.show', compact('alert', 'student'));
    }

    public function markAsRead(Alert $alert)
    {
        $alert->update(['is_read' => true]);

        return back()->with('success', 'Alert marked as read.');
    }
}
