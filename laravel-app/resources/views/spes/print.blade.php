<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $application->reference_no }} — SPES Application</title>
    <style>
        @page { size: A4 portrait; margin: 7mm; }
        html, body { margin: 0; background: #e7e5e4; color: #111; }
        body { font-family: "Times New Roman", Times, serif; font-size: 9pt; line-height: 1.15; }
        .actions { width: 210mm; margin: 12px auto 0; font-family: system-ui, sans-serif; }
        button, a.btn { font-size: 13px; margin-right: 8px; }
        .sheet {
            width: 210mm;
            height: 297mm;
            margin: 12px auto 24px;
            background: #fff;
            box-sizing: border-box;
            padding: 7mm 8mm;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        h1 { font-size: 12.5pt; margin: 0; letter-spacing: 0.03em; }
        h2 { font-size: 10.5pt; margin: 1px 0 0; font-weight: 600; }
        h3 { font-size: 8.5pt; margin: 5px 0 0; text-transform: uppercase; letter-spacing: 0.04em; }
        .center { text-align: center; }
        .muted { color: #333; margin: 2px 0 0; font-size: 8.5pt; }
        .rule { border-top: 1px solid #111; margin: 4px 0 1px; }
        table { width: 100%; border-collapse: collapse; margin-top: 2px; }
        th, td { border: 1px solid #222; padding: 1.5px 4px; vertical-align: top; text-align: left; }
        th { width: 18%; background: #f3f3f3; font-weight: 700; }
        td { width: 32%; }
        p { margin: 3px 0 0; font-size: 8.5pt; }
        .sign { margin-top: 8px; display: flex; justify-content: space-between; gap: 18px; }
        .sign div { width: 46%; text-align: center; font-size: 8.5pt; }
        .line { border-top: 1px solid #111; margin-top: 16px; padding-top: 2px; }
        @media print {
            html, body { background: #fff; }
            .actions { display: none; }
            .sheet { width: auto; height: auto; margin: 0; padding: 0; overflow: visible; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Print</button>
        <a class="btn" href="{{ !empty($studentMode) ? route('student.spes.show', $application) : route('spes.show', $application) }}">Back to record</a>
        <span style="font-family: system-ui, sans-serif; font-size: 12px; color: #444;">A4, one page</span>
    </div>
    <div class="sheet">
    <div class="center">
        <div>Republic of the Philippines</div>
        <div>Department of Labor and Employment</div>
        <h1>SPECIAL PROGRAM FOR THE EMPLOYMENT OF STUDENTS</h1>
        <h2>Application Form</h2>
        <p class="muted">RA 7323, as amended by RA 9547 and RA 10917 · Reference {{ $application->reference_no }}</p>
    </div>
    <div class="rule"></div>
    <h3>1. Personal information</h3>
    <table>
        <tr><th>Applicant</th><td>{{ $application->fullName() }}</td><th>Student ID</th><td>{{ $application->student_id ?: '—' }}</td></tr>
        <tr><th>Sex</th><td>{{ \App\Models\SpesApplication::SEXES[$application->sex] ?? '' }}</td><th>Civil status</th><td>{{ \App\Models\SpesApplication::CIVIL_STATUSES[$application->civil_status] ?? '' }}</td></tr>
        <tr><th>Birth date</th><td>{{ $application->birth_date?->format('F j, Y') }}</td><th>Age</th><td>{{ $application->age }}</td></tr>
        <tr><th>Place of birth</th><td>{{ $application->place_of_birth }}</td><th>Contact</th><td>{{ $application->contact_number }}</td></tr>
        <tr><th>Email</th><td colspan="3">{{ $application->email ?: '—' }}</td></tr>
        <tr><th>Address</th><td colspan="3">{{ $application->address }}, {{ $application->barangay }}, {{ $application->city_municipality }}, {{ $application->province }} {{ $application->zip_code }}</td></tr>
    </table>
    <h3>2. Family and income</h3>
    <table>
        <tr><th>Father</th><td>{{ $application->father_name ?: '—' }}</td><th>Occupation</th><td>{{ $application->father_occupation ?: '—' }}</td></tr>
        <tr><th>Mother</th><td>{{ $application->mother_name ?: '—' }}</td><th>Occupation</th><td>{{ $application->mother_occupation ?: '—' }}</td></tr>
        <tr><th>Parents’ annual income</th><td>PHP {{ number_format((float) $application->parents_annual_income, 2) }}</td><th>Siblings</th><td>{{ $application->number_of_siblings }}</td></tr>
    </table>
    <h3>3. Education</h3>
    <table>
        <tr><th>Education</th><td>{{ \App\Models\SpesApplication::EDUCATION_LEVELS[$application->education_level] ?? '' }}</td><th>Enrollment</th><td>{{ \App\Models\SpesApplication::ENROLLMENT_STATUSES[$application->enrollment_status] ?? '' }}</td></tr>
        <tr><th>School</th><td>{{ $application->school_name }}</td><th>Year level</th><td>{{ $application->year_level }}</td></tr>
        <tr><th>Course</th><td>{{ $application->course ?: '—' }}</td><th>General average</th><td>{{ $application->general_average !== null ? number_format((float) $application->general_average, 2) : '—' }}</td></tr>
        <tr><th>School address</th><td colspan="3">{{ $application->school_address ?: '—' }}</td></tr>
    </table>
    <h3>4. Work preference</h3>
    <table>
        <tr><th>Work period</th><td>{{ \App\Models\SpesApplication::WORK_PERIODS[$application->work_period] ?? '' }} {{ $application->application_year }}</td><th>Working days</th><td>{{ $application->preferred_days }}</td></tr>
        <tr><th>Beneficiary</th><td>{{ \App\Models\SpesApplication::BENEFICIARY_TYPES[$application->beneficiary_type] ?? '' }}</td><th>Previous SPES</th><td>{{ $application->previous_spes ? 'Yes, '.$application->previous_year : 'No' }}</td></tr>
        <tr><th>Preferred assignment</th><td colspan="3">{{ $application->preferred_assignment ?: '—' }}</td></tr>
        <tr><th>Skills</th><td colspan="3">{{ $application->skills ?: '—' }}</td></tr>
    </table>
    <h3>5. Documents</h3>
    <table>
        <tr><th>Document</th><th>Status</th></tr>
        @foreach(\App\Models\SpesApplication::DOCUMENTS as $field => $label)
        <tr><td>{{ $label }}</td><td>{{ $application->{$field} ? 'Received' : 'Missing' }}</td></tr>
        @endforeach
    </table>
    <h3>6. Certification and office</h3>
    <table>
        <tr><th>Signed name</th><td>{{ $application->applicant_signature }}</td><th>Application date</th><td>{{ $application->application_date?->format('F j, Y') }}</td></tr>
        <tr><th>Office status</th><td colspan="3">{{ $application->statusLabel() }}{{ $application->remarks ? ' — '.$application->remarks : '' }}</td></tr>
    </table>
    <p class="note">Wage sharing for the engagement: employer 40%, DOLE 60%. Placement is subject to PESO and DOLE review, including the regional poverty threshold for family income.</p>
    <p class="note">I certify that the information above is true and correct, and that I will complete the SPES work period and continue my studies.</p>
    <div class="sign">
        <div>
            <div class="line">{{ $application->applicant_signature }}<br>Applicant · {{ $application->application_date?->format('F j, Y') }}</div>
        </div>
        <div>
            <div class="line">PESO / DOLE receiving officer</div>
        </div>
    </div>
    </div>
</body>
</html>
