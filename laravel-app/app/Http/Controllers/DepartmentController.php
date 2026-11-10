<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Department;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('college')->withCount('programs')->paginate(15);

        return view('data-management.departments.index', compact('departments'));
    }

    public function create()
    {
        $colleges = College::orderBy('college_name')->get();

        return view('data-management.departments.create', compact('colleges'));
    }

    public function store(Request $request, AuditLogger $audit)
    {
        Department::create($this->validated($request));
        $audit->log('create', 'departments', $request->department_name);

        return redirect()->route('data-management.departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        $colleges = College::orderBy('college_name')->get();

        return view('data-management.departments.edit', compact('department', 'colleges'));
    }

    public function update(Request $request, Department $department, AuditLogger $audit)
    {
        $department->update($this->validated($request, $department->id));
        $audit->log('update', 'departments', $department->department_name);

        return redirect()->route('data-management.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department, AuditLogger $audit)
    {
        $department->delete();
        $audit->log('delete', 'departments', $department->department_name);

        return redirect()->route('data-management.departments.index')->with('success', 'Department deleted.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'college_id' => [
                'required',
                'exists:colleges,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $college = College::find($value);
                    if (! $college || $college->college_code !== config('scope.college_code')) {
                        $fail('Department must belong to '.config('scope.college_code').'.');
                    }
                },
            ],
            'department_name' => ['required', 'string', 'max:255', 'in:'.implode(',', array_values(config('scope.departments')))],
            'department_code' => [
                'required',
                'string',
                'max:20',
                'unique:departments,department_code,'.($id ?? 'NULL'),
                'in:'.implode(',', array_keys(config('scope.departments'))),
            ],
        ], [
            'department_name.in' => 'Choose Information Technology, Computer Engineering, or Electrical Engineering.',
            'department_code.in' => 'Choose IT, CpE, or EE.',
        ]);
    }
}
