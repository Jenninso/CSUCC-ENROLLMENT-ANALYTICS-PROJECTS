<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function index()
    {
        $colleges = College::withCount(['departments', 'programs'])->paginate(15);

        return view('data-management.colleges.index', compact('colleges'));
    }

    public function create()
    {
        return view('data-management.colleges.create');
    }

    public function store(Request $request, AuditLogger $audit)
    {
        if (College::query()->exists()) {
            return back()->withErrors([
                'college_code' => 'This system supports only '.config('scope.college_code').' ('.config('scope.college_name').').',
            ])->withInput();
        }

        College::create($this->validated($request));
        $audit->log('create', 'colleges', $request->college_name);

        return redirect()->route('data-management.colleges.index')->with('success', 'College created.');
    }

    public function edit(College $college)
    {
        return view('data-management.colleges.edit', compact('college'));
    }

    public function update(Request $request, College $college, AuditLogger $audit)
    {
        $college->update($this->validated($request, $college->id));
        $audit->log('update', 'colleges', $college->college_name);

        return redirect()->route('data-management.colleges.index')->with('success', 'College updated.');
    }

    public function destroy(College $college, AuditLogger $audit)
    {
        $college->delete();
        $audit->log('delete', 'colleges', $college->college_name);

        return redirect()->route('data-management.colleges.index')->with('success', 'College deleted.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'college_name' => [
                'required',
                'string',
                'max:255',
                'unique:colleges,college_name,'.($id ?? 'NULL'),
                'in:'.config('scope.college_name'),
            ],
            'college_code' => [
                'required',
                'string',
                'max:20',
                'unique:colleges,college_code,'.($id ?? 'NULL'),
                'in:'.config('scope.college_code'),
            ],
        ], [
            'college_name.in' => 'Only '.config('scope.college_name').' is allowed.',
            'college_code.in' => 'Only '.config('scope.college_code').' is allowed.',
        ]);
    }
}
