@extends('layouts.app')
@section('title', $application->reference_no)

@section('content')
@php $studentMode = ! empty($studentMode); @endphp
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <div>
        <a href="{{ $studentMode ? route('student.spes.index') : route('spes.index') }}" class="ui-link text-sm">← Back to SPES applications</a>
        <h3 class="ui-page-title mt-2">{{ $application->fullName() }}</h3>
        <p class="ui-page-sub font-mono">{{ $application->reference_no }}</p>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
        <a href="{{ $studentMode ? route('student.spes.print', $application) : route('spes.print', $application) }}" target="_blank" class="ui-btn-secondary">Print form</a>
        @if($studentMode)
            @if($application->studentMayEdit())
            <a href="{{ route('student.spes.edit', $application) }}" class="ui-btn-primary">Edit</a>
            @endif
            @if($application->status === 'submitted')
            <form method="POST" action="{{ route('student.spes.destroy', $application) }}" onsubmit="return confirm('Withdraw this application?')">
                @csrf @method('DELETE')
                <button type="submit" class="ui-btn-secondary text-red-700 border-red-200 hover:bg-red-50">Withdraw</button>
            </form>
            @endif
        @elseif(auth()->user()?->canAccess('enrollment.manage'))
        <a href="{{ route('spes.edit', $application) }}" class="ui-btn-primary">Edit</a>
        <form method="POST" action="{{ route('spes.destroy', $application) }}" onsubmit="return confirm('Delete {{ $application->reference_no }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="ui-btn-secondary text-red-700 border-red-200 hover:bg-red-50">Delete</button>
        </form>
        @if($application->user_id && $application->isLocked())
        <form method="POST" action="{{ route('spes.student-access', $application) }}">
            @csrf
            <input type="hidden" name="allow" value="{{ $application->student_can_edit ? '0' : '1' }}">
            <button type="submit" class="ui-btn-secondary">{{ $application->student_can_edit ? 'Lock student editing' : 'Allow student to edit' }}</button>
        </form>
        @endif
        @endif
    </div>
</div>

@if($studentMode && $application->isLocked() && ! $application->student_can_edit)
<div class="mb-4 max-w-5xl rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
    This application is {{ strtolower($application->statusLabel()) }}. You cannot edit it unless the office handler allows another edit.
</div>
@elseif($studentMode && $application->isLocked() && $application->student_can_edit)
<div class="mb-4 max-w-5xl rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
    The office allowed one more edit. After you save, the application locks again.
</div>
@endif

<style>
    .spes-sheet { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .spes-sheet th, .spes-sheet td { border: 1px solid var(--theme-border); padding: 0.7rem 0.9rem; vertical-align: top; text-align: left; }
    .spes-sheet tbody th { width: 18%; background: color-mix(in srgb, var(--theme-bg) 70%, var(--theme-panel)); color: var(--theme-muted); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; white-space: nowrap; }
    .spes-sheet td { width: 32%; color: var(--theme-text); font-weight: 600; }
    .spes-sheet thead th { background: color-mix(in srgb, var(--theme-primary) 10%, var(--theme-panel)); color: var(--theme-text); }
</style>

@php
    $text = fn ($value) => filled($value) ? $value : '—';
    $address = collect([
        $application->address,
        $application->barangay,
        $application->city_municipality,
        trim(($application->province ?? '').' '.($application->zip_code ?? '')),
    ])->map(fn ($part) => trim((string) $part))->filter()->implode(', ');
    $sections = [
        '1. Personal information' => [
            ['Student ID' => $text($application->student_id), 'Applicant' => $application->fullName()],
            ['Sex' => \App\Models\SpesApplication::SEXES[$application->sex] ?? $text($application->sex), 'Civil status' => \App\Models\SpesApplication::CIVIL_STATUSES[$application->civil_status] ?? $text($application->civil_status)],
            ['Birth date' => $application->birth_date?->format('F j, Y') ?: '—', 'Age' => $text($application->age)],
            ['Place of birth' => $text($application->place_of_birth), 'Contact' => $text($application->contact_number)],
            ['Email' => $text($application->email)],
            ['Address' => $address ?: '—'],
        ],
        '2. Family and income' => [
            ['Father' => $text($application->father_name), 'Occupation' => $text($application->father_occupation)],
            ['Mother' => $text($application->mother_name), 'Occupation' => $text($application->mother_occupation)],
            ['Parents’ annual income' => 'PHP '.number_format((float) $application->parents_annual_income, 2), 'Siblings' => $text($application->number_of_siblings)],
        ],
        '3. Education' => [
            ['Level' => \App\Models\SpesApplication::EDUCATION_LEVELS[$application->education_level] ?? $text($application->education_level), 'Enrollment' => \App\Models\SpesApplication::ENROLLMENT_STATUSES[$application->enrollment_status] ?? $text($application->enrollment_status)],
            ['School' => $text($application->school_name), 'Year level' => $text($application->year_level)],
            ['Course' => $text($application->course), 'General average' => $application->general_average !== null ? number_format((float) $application->general_average, 2) : '—'],
            ['School address' => $text($application->school_address)],
        ],
        '4. Work preference' => [
            ['Work period' => (\App\Models\SpesApplication::WORK_PERIODS[$application->work_period] ?? $application->work_period).' '.$application->application_year, 'Working days' => $text($application->preferred_days)],
            ['Beneficiary' => \App\Models\SpesApplication::BENEFICIARY_TYPES[$application->beneficiary_type] ?? $text($application->beneficiary_type), 'Previous SPES' => $application->previous_spes ? 'Yes'.($application->previous_year ? ', '.$application->previous_year : '') : 'No'],
            ['Preferred assignment' => $text($application->preferred_assignment)],
            ['Skills' => $text($application->skills)],
        ],
    ];
@endphp

<div class="max-w-6xl space-y-5">
    <section class="ui-panel overflow-hidden">
        <table class="spes-sheet">
            <tbody>
                <tr>
                    <th>Reference</th>
                    <td class="font-mono">{{ $application->reference_no }}</td>
                    <th>Status</th>
                    <td><span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $application->statusBadgeClass() }}">{{ $application->statusLabel() }}</span></td>
                </tr>
                <tr>
                    <th>Student ID</th>
                    <td class="font-mono">{{ $text($application->student_id) }}</td>
                    <th>Documents</th>
                    <td>{{ $application->documentsAttached() }} of {{ count(\App\Models\SpesApplication::DOCUMENTS) }} on file</td>
                </tr>
            </tbody>
        </table>
    </section>

    @foreach($sections as $title => $rows)
    <section class="ui-panel overflow-hidden">
        <div class="px-5 py-3 border-b border-[var(--theme-border)]">
            <h4 class="text-sm font-semibold text-gray-900">{{ $title }}</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="spes-sheet">
                <tbody>
                    @foreach($rows as $row)
                    <tr>
                        @if(count($row) === 1)
                            <th>{{ array_key_first($row) }}</th>
                            <td colspan="3">{{ reset($row) }}</td>
                        @else
                            @foreach($row as $label => $value)
                                <th>{{ $label }}</th>
                                <td>{{ $value }}</td>
                            @endforeach
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endforeach

    <section class="ui-panel overflow-hidden">
        <div class="px-5 py-3 border-b border-[var(--theme-border)] flex items-center justify-between gap-3">
            <h4 class="text-sm font-semibold text-gray-900">5. Documents</h4>
            <p class="text-xs text-gray-500">{{ $application->documentsAttached() }} received</p>
        </div>
        <div class="overflow-x-auto">
            <table class="spes-sheet">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\SpesApplication::DOCUMENTS as $field => $label)
                    <tr>
                        <td class="!font-medium">{{ $label }}</td>
                        <td class="{{ $application->{$field} ? 'text-emerald-800' : 'text-gray-500' }}">{{ $application->{$field} ? 'Received' : 'Missing' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="ui-panel overflow-hidden">
        <div class="px-5 py-3 border-b border-[var(--theme-border)]">
            <h4 class="text-sm font-semibold text-gray-900">6. Certification and office</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="spes-sheet">
                <tbody>
                    <tr>
                        <th>Signed name</th>
                        <td>{{ $text($application->applicant_signature) }}</td>
                        <th>Application date</th>
                        <td>{{ $application->application_date?->format('F j, Y') ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Office status</th>
                        <td>{{ $application->statusLabel() }}</td>
                        @unless($studentMode)
                        <th>Encoded by</th>
                        <td>{{ $application->encoder?->fullName() ?: '—' }}</td>
                        @else
                        <th>Student editing</th>
                        <td>{{ $application->studentMayEdit() ? 'Open' : 'Locked' }}</td>
                        @endunless
                    </tr>
                    @if($application->remarks)
                    <tr>
                        <th>Remarks</th>
                        <td colspan="3" class="whitespace-pre-line !font-medium">{{ $application->remarks }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
