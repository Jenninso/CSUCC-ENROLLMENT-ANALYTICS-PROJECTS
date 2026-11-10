<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Program;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::with(['department.college'])->paginate(15);

        return view('data-management.programs.index', compact('programs'));
    }

    public function create()
    {
        $departments = Department::with('college')->orderBy('department_name')->get();

        return view('data-management.programs.create', compact('departments'));
    }

    public function store(Request $request, AuditLogger $audit)
    {
        Program::create($this->validated($request));
        $audit->log('create', 'programs', $request->program_name);

        return redirect()->route('data-management.programs.index')->with('success', 'Program created.');
    }

    public function edit(Program $program)
    {
        $departments = Department::with('college')->orderBy('department_name')->get();

        return view('data-management.programs.edit', compact('program', 'departments'));
    }

    public function update(Request $request, Program $program, AuditLogger $audit)
    {
        $program->update($this->validated($request, $program->id));
        $audit->log('update', 'programs', $program->program_name);

        return redirect()->route('data-management.programs.index')->with('success', 'Program updated.');
    }

    public function destroy(Program $program, AuditLogger $audit)
    {
        $program->delete();
        $audit->log('delete', 'programs', $program->program_name);

        return redirect()->route('data-management.programs.index')->with('success', 'Program deleted.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'department_id' => [
                'required',
                'exists:departments,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $department = Department::with('college')->find($value);
                    if (! $department || $department->college?->college_code !== config('scope.college_code')) {
                        $fail('Program must belong to '.config('scope.college_code').'.');
                    }
                    $allowedDepartments = array_keys(config('scope.departments'));
                    if ($department && ! in_array($department->department_code, $allowedDepartments, true)) {
                        $fail('Program must belong to a CEIT Information Technology or Engineering department.');
                    }
                },
            ],
            'program_name' => ['required', 'string', 'max:255', 'in:'.implode(',', array_column(config('scope.programs'), 'name'))],
            'program_code' => [
                'required',
                'string',
                'max:20',
                'unique:programs,program_code,'.($id ?? 'NULL'),
                'in:'.implode(',', array_keys(config('scope.programs'))),
            ],
            'degree_level' => ['required', 'string', 'max:50', 'in:'.config('scope.degree_level')],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'program_name.in' => 'Choose a CEIT Information Technology or Engineering course.',
            'program_code.in' => 'Choose BSIT, BSCpE, or BSEE.',
        ]);
    }
}
