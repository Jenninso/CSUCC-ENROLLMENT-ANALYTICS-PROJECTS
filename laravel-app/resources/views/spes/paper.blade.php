@php
    $editing = $editing ?? false;
    $studentMode = $studentMode ?? false;
    $paper = $application?->paper() ?? ($prefill ?? []);
    $aliases = [
        'id_no' => 'student_id',
        'mobile' => 'contact_number',
        'home_purok' => 'address',
        'home_barangay' => 'barangay',
        'home_city' => 'city_municipality',
        'home_province' => 'province',
        'home_zip' => 'zip_code',
        'sex_at_birth' => 'sex',
        'signature_name' => 'applicant_signature',
    ];
    $raw = function (string $key) use ($paper, $aliases) {
        $value = old($key, $paper[$key] ?? null);
        if ($value === null || $value === '') {
            $alt = $aliases[$key] ?? null;
            $value = $alt ? old($alt, $paper[$alt] ?? '') : '';
        }
        return $value;
    };
    $on = fn (string $key, string $expect) => (string) $raw($key) === $expect;
    $supportOn = function (string $item) use ($paper) {
        $support = old('support', $paper['support'] ?? []);
        return is_array($support) && in_array($item, $support, true);
    };
    $line = function (string $name, array $attrs = []) use ($editing, $raw) {
        $value = e($raw($name));
        if (! $editing) {
            return '<span class="fill">'.$value.'</span>';
        }
        $type = $attrs['type'] ?? 'text';
        $extra = '';
        foreach ($attrs as $attr => $attrValue) {
            if ($attr === 'type') {
                continue;
            }
            $extra .= ' '.$attr.'="'.e($attrValue).'"';
        }
        return '<input class="ln" type="'.$type.'" name="'.$name.'" value="'.$value.'"'.$extra.'>';
    };
    $choice = function (string $name, string $value, string $label, string $kind = 'radio') use ($editing, $on) {
        $checked = $on($name, $value);
        if (! $editing) {
            $mark = $kind === 'radio' ? ($checked ? '◉' : '○') : ($checked ? '☑' : '☐');
            return '<span class="choice">'.$mark.' '.$label.'</span>';
        }
        $type = $kind === 'check' ? 'checkbox' : 'radio';
        $nameAttr = $kind === 'check' ? $name.'[]' : $name;
        $checkedAttr = $checked ? ' checked' : '';
        return '<label class="choice"><input type="'.$type.'" name="'.$nameAttr.'" value="'.$value.'"'.$checkedAttr.'> '.$label.'</label>';
    };
    $support = function (string $value, string $label) use ($editing, $supportOn) {
        $checked = $supportOn($value);
        if (! $editing) {
            return '<span class="choice">'.($checked ? '☑' : '☐').' '.$label.'</span>';
        }
        $checkedAttr = $checked ? ' checked' : '';
        return '<label class="choice"><input type="checkbox" name="support[]" value="'.$value.'"'.$checkedAttr.'> '.$label.'</label>';
    };
    $tracks = [
        'gas' => 'General Academic Strand (GAS) – Academic Track',
        'humss' => 'Humanities and Social Sciences (HUMSS) Strand – Academic Track',
        'stem' => 'Science, Technology, Engineering, and Mathematics (STEM) Strand – Academic Track',
        'abm' => 'Accountancy, Business and Management (ABM) Strand – Academic Track',
        'ict' => 'Information and Communication Technology (ICT) – TVL Track',
        'he' => 'Home Economics (HE) – TVL Track',
        'agri' => 'Agri Fishery Arts – TVL Track',
        'industrial' => 'Industrial Arts – TVL Track',
        'arts' => 'Arts and Design Track',
        'sports' => 'Sports Track',
        'na' => 'Not Applicable',
    ];
@endphp

<style>
    .spr-wrap { overflow-x: auto; }
    .spr-sheet {
        width: 8.5in;
        height: 13in;
        margin: 0 auto 16px;
        background: #fff;
        color: #111;
        box-sizing: border-box;
        padding: 0.28in 0.32in 0.22in;
        font-family: "Times New Roman", Times, serif;
        font-size: 8pt;
        line-height: 1.22;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        overflow: hidden;
        page-break-after: avoid;
        page-break-inside: avoid;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .spr-sheet h2 { font-size: 8.5pt; margin: 0; }
    .spr-sheet p { margin: 0; }
    .spr-head { margin: -0.03in -0.07in 4px; }
    .spr-head img { width: 100%; height: auto; display: block; }
    .spr-title { text-align: center; margin: 2px 0; }
    .spr-title span { display: inline-block; border: 1.5px solid #111; font-weight: 700; letter-spacing: 0.03em; padding: 0 8px; font-size: 10pt; line-height: 1.2; }
    .spr-row { display: flex; gap: 6px; align-items: flex-end; margin-top: 0; }
    .spr-row .grow { flex: 1; min-width: 0; }
    .spr-label { white-space: nowrap; }
    .ln, .fill { border: 0; border-bottom: 1px solid #111; background: transparent; font: inherit; color: #111; width: 100%; min-height: 13px; height: 14px; padding: 0 2px; display: inline-block; }
    .spr-wrap.is-editing .spr-sheet { height: auto; min-height: 13in; overflow: visible; justify-content: space-between; }
    .spr-wrap.is-editing .ln {
        height: 1.35rem;
        min-height: 1.35rem;
        background: #fffdf2;
        pointer-events: auto;
        position: relative;
        z-index: 1;
        cursor: text;
    }
    .spr-wrap.is-editing .choice input { width: 14px; height: 14px; cursor: pointer; }
    .choice { white-space: nowrap; margin-right: 5px; }
    .choice input { margin: 0 1px 0 0; width: 9px; height: 9px; }
    .spr-grid { display: grid; gap: 0 8px; margin-top: 1px; }
    .spr-grid.two { grid-template-columns: 1fr 1fr; }
    .spr-box { border: 1px solid #111; padding: 2px 5px; margin-top: 2px; }
    .spr-small { font-size: 7pt; line-height: 1.2; text-align: justify; }
    .spr-center { text-align: center; font-weight: 700; margin-top: 3px; }
    .spr-sign { margin-left: auto; width: 2.4in; text-align: center; font-weight: 700; border-top: 1px solid #111; padding-top: 1px; }
    .spr-sign-img { display: block; width: 2.2in; height: 0.42in; object-fit: contain; margin: 0 0 1px auto; }
    .spr-pad { margin-left: auto; width: 2.6in; margin-top: 4px; }
    .spr-pad canvas { width: 100%; height: 58px; border: 1px dashed #111; background: #fff; touch-action: none; display: block; cursor: crosshair; }
    .spr-pad button { margin-top: 3px; font: inherit; font-size: 8pt; background: #fff; border: 1px solid #111; padding: 1px 6px; cursor: pointer; }
    .spr-footer { display: flex; justify-content: space-between; align-items: flex-end; gap: 8px; margin-top: 0; font-size: 7pt; }
    .office-note { text-align: center; font-weight: 700; letter-spacing: 0.04em; margin: 3px 0 1px; }
    @media print {
        .spr-sheet { margin: 0; box-shadow: none; }
    }
</style>

<div class="spr-wrap {{ $editing ? 'is-editing' : '' }}">
    <section class="spr-sheet">
        <div class="spr-head">
            <img src="{{ $headerSrc ?? asset('images/branding/csu-cabadbaran-header.png').'?v=4' }}" alt="Caraga State University Cabadbaran City">
        </div>
        <div class="spr-title"><span>STUDENT PERSONAL RECORD (SPR)</span></div>
        <p><strong>Direction:</strong> Please fill-out the required information clearly. Put “N/A” for information not applicable to you.</p>
        <h2>STUDENT'S PERSONAL INFORMATION</h2>
        @if($editing)
            <input type="hidden" name="student_record_id" value="{{ $raw('student_record_id') }}">
        @endif
        <div class="spr-row">
            <span class="spr-label">Student's Name:</span>
            <span class="grow">{!! $line('last_name', ['required' => 'required', 'placeholder' => 'Last Name']) !!}<br><span class="spr-small">Last Name</span></span>
            <span class="grow">{!! $line('first_name', ['required' => 'required', 'placeholder' => 'First Name']) !!}<br><span class="spr-small">First Name (include name extension)</span></span>
            <span class="grow">{!! $line('middle_name', ['placeholder' => 'Middle Name']) !!}<br><span class="spr-small">Middle Name</span></span>
            <span style="width: 1.5in">{!! $line('id_no') !!}<br><span class="spr-small">I.D. No.</span></span>
        </div>
        <div class="spr-row" style="align-items: flex-start; margin-top: 6px;">
            <span class="spr-label">Student Status:</span>
            <div>
                <div>{!! $choice('student_level', 'post_graduate', 'Post Graduate', 'bracket') !!}</div>
                <div>{!! $choice('student_level', 'graduate', 'Graduate', 'bracket') !!} :
                    {!! $choice('admission_type', 'new_student', 'New Student') !!}
                    {!! $choice('admission_type', 'transferee', 'Transferee') !!}
                    {!! $choice('admission_type', 'old_student', 'Old Student') !!}
                    {!! $choice('admission_type', 'returnee', 'Returnee') !!}
                </div>
                <div>{!! $choice('student_level', 'undergraduate', 'Undergraduate', 'bracket') !!} :
                    {!! $choice('admission_type', 'new_student', 'New Student') !!}
                    {!! $choice('admission_type', 'transferee', 'Transferee') !!}
                    {!! $choice('admission_type', 'old_student', 'Old Student') !!}
                    {!! $choice('admission_type', 'returnee', 'Returnee') !!}
                </div>
                <div>{!! $choice('student_level', 'certificate', 'Certificate Program', 'bracket') !!}</div>
            </div>
        </div>
        <div class="spr-row">
            <span class="spr-label">Date of Birth:</span><span class="grow">{!! $line('birth_date', ['type' => 'date', 'required' => 'required']) !!}</span>
            <span class="spr-label">Age:</span><span style="width: .7in">{!! $line('age', ['type' => 'number', 'min' => '1', 'max' => '120']) !!}</span>
            <span class="spr-label">Civil Status:</span><span class="grow">{!! $line('civil_status') !!}</span>
            <span class="spr-label">Sex at Birth:</span><span class="grow">{!! $line('sex_at_birth') !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Sex Orientation, Gender Identity and Expression (SOGIE):</span>
            <span class="grow">{!! $line('sogie') !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Place of Birth:</span><span class="grow">{!! $line('place_of_birth') !!}</span>
            <span class="spr-label">Citizenship:</span><span class="grow">{!! $line('citizenship') !!}</span>
            <span class="spr-label">Religion:</span><span class="grow">{!! $line('religion') !!}</span>
        </div>
        <p style="margin-top: 4px;">Home Address:</p>
        <div class="spr-row">
            <span class="grow">{!! $line('home_purok') !!}<br><span class="spr-small">Purok</span></span>
            <span class="grow">{!! $line('home_barangay') !!}<br><span class="spr-small">Barangay</span></span>
            <span class="grow">{!! $line('home_city') !!}<br><span class="spr-small">City/Municipality</span></span>
            <span class="grow">{!! $line('home_province') !!}<br><span class="spr-small">Province</span></span>
            <span style="width: .9in">{!! $line('home_zip') !!}<br><span class="spr-small">Zip Code</span></span>
        </div>
        <p style="margin-top: 4px;">Current Address:</p>
        <div class="spr-row">
            <span class="grow">{!! $line('current_purok') !!}<br><span class="spr-small">Purok</span></span>
            <span class="grow">{!! $line('current_barangay') !!}<br><span class="spr-small">Barangay</span></span>
            <span class="grow">{!! $line('current_city') !!}<br><span class="spr-small">City/Municipality</span></span>
            <span class="grow">{!! $line('current_province') !!}<br><span class="spr-small">Province</span></span>
            <span style="width: .9in">{!! $line('current_zip') !!}<br><span class="spr-small">Zip Code</span></span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Mobile No.:</span><span class="grow">{!! $line('mobile') !!}</span>
            <span class="spr-label">Ethnicity:</span><span class="grow">{!! $line('ethnicity') !!}</span>
            <span class="spr-label">Email Address:</span><span class="grow">{!! $line('email', ['type' => 'email']) !!}</span>
        </div>
        <p style="margin-top: 6px;">Are you the first member of the family to enroll in college?
            {!! $choice('first_in_family', 'yes', 'Yes') !!} {!! $choice('first_in_family', 'no', 'No') !!}
        </p>
        <p>What is your preferred hand for activities such as writing and other manual tasks?
            {!! $choice('preferred_hand', 'right', 'Right hand') !!}
            {!! $choice('preferred_hand', 'left', 'Left hand') !!}
            {!! $choice('preferred_hand', 'both', 'Both hands can do equally well') !!}
        </p>
        <div class="spr-row">
            <span>Are you a person with special needs?</span>
            {!! $choice('special_needs', 'yes', 'Yes') !!} {!! $choice('special_needs', 'no', 'No') !!}
            <span class="spr-label">If Yes, please specify:</span><span class="grow">{!! $line('special_needs_detail') !!}</span>
        </div>
        <div class="spr-row">
            <span>Are you a 4P's Beneficiary?</span>
            {!! $choice('four_ps', 'yes', 'Yes') !!} {!! $choice('four_ps', 'no', 'No') !!}
            <span class="spr-label">If Yes, please provide the Household ID No:</span><span class="grow">{!! $line('household_id') !!}</span>
        </div>
        <div class="spr-row">
            <span>Are you a solo parent?</span>
            {!! $choice('solo_parent', 'yes', 'Yes') !!} {!! $choice('solo_parent', 'no', 'No') !!}
            <span class="spr-label">Number of children:</span><span style="width: .7in">{!! $line('number_of_children', ['type' => 'number', 'min' => '0']) !!}</span>
            <span>Are you living with your parents?</span>
            {!! $choice('living_with_parents', 'yes', 'Yes') !!} {!! $choice('living_with_parents', 'no', 'No') !!}
        </div>
        <div class="spr-row">
            <span class="spr-label">Father's Name:</span><span class="grow">{!! $line('father_name') !!}</span>
            {!! $choice('father_status', 'living', 'Living') !!} {!! $choice('father_status', 'deceased', 'Deceased') !!}
            <span class="spr-label">Occupation:</span><span class="grow">{!! $line('father_occupation') !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Mother's Name:</span><span class="grow">{!! $line('mother_name') !!}</span>
            {!! $choice('mother_status', 'living', 'Living') !!} {!! $choice('mother_status', 'deceased', 'Deceased') !!}
            <span class="spr-label">Occupation:</span><span class="grow">{!! $line('mother_occupation') !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Estimated Household Monthly Income:</span><span class="grow">{!! $line('estimated_income') !!}</span>
        </div>
        <p style="margin-top: 4px;">Source of Financial Support: <span class="spr-small">(you may check more than one)</span></p>
        <p>
            {!! $support('parents', 'Parents') !!}
            {!! $support('guardian', 'Guardian') !!}
            {!! $support('student_assistant', 'Student Assistant') !!}
            {!! $support('employed', 'Employed') !!}
            {!! $choice('employed_time', 'full_time', 'Full-time') !!}
            {!! $choice('employed_time', 'part_time', 'Part-time') !!}
        </p>
        <div class="spr-row">
            {!! $support('scholarship', 'Scholarship') !!}
            <span class="spr-label">Please specify:</span><span class="grow">{!! $line('scholarship_specify') !!}</span>
            {!! $support('others', 'Others') !!}
            <span class="spr-label">Please specify:</span><span class="grow">{!! $line('others_specify') !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Guardian's Name:</span><span class="grow">{!! $line('guardian_name') !!}</span>
            <span class="spr-label">Relationship:</span><span class="grow">{!! $line('relationship') !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Parent's / Guardian's Mobile No.:</span><span class="grow">{!! $line('guardian_mobile') !!}</span>
            <span class="spr-label">Address of the parent/guardian:</span><span class="grow">{!! $line('guardian_address') !!}</span>
        </div>
        <h2>STUDENT'S OTHER SCHOLASTIC INFORMATION</h2>
        <div class="spr-row">
            <span class="spr-label">Elementary School:</span><span class="grow">{!! $line('elementary_school') !!}</span>
            <span class="spr-label">Address:</span><span class="grow">{!! $line('elementary_address') !!}</span>
            <span class="spr-label">SY Graduated:</span><span style="width: 1in">{!! $line('elementary_sy') !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Secondary School (SHS):</span><span class="grow">{!! $line('secondary_school') !!}</span>
            <span class="spr-label">Address:</span><span class="grow">{!! $line('secondary_address') !!}</span>
            <span class="spr-label">SY Graduated:</span><span style="width: 1in">{!! $line('secondary_sy') !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">School Last Attended:</span><span class="grow">{!! $line('last_school') !!}</span>
            <span class="spr-label">Address:</span><span class="grow">{!! $line('last_school_address') !!}</span>
            <span class="spr-label">Inclusive Years:</span><span style="width: 1in">{!! $line('last_school_years') !!}</span>
        </div>
        <p style="margin-top: 6px;"><strong>Your Senior High School Track</strong></p>
        <div class="spr-grid two">
            <div>
                @foreach(['gas', 'humss', 'stem', 'abm', 'ict'] as $track)
                    <div>{!! $choice('shs_track', $track, $tracks[$track]) !!}</div>
                @endforeach
            </div>
            <div>
                @foreach(['he', 'agri', 'industrial', 'arts', 'sports', 'na'] as $track)
                    <div>{!! $choice('shs_track', $track, $tracks[$track]) !!}</div>
                @endforeach
            </div>
        </div>
        <p style="margin-top: 6px;"><strong>Academic Degree details</strong> <span class="spr-small">(for Graduate, 2nd Courser, and Units Earner students only)</span></p>
        <div class="spr-row"><span class="spr-label">Name of the Baccalaureate degree last taken:</span><span class="grow">{!! $line('baccalaureate_degree') !!}</span></div>
        <div class="spr-row"><span class="spr-label">Name of School where the degree was taken:</span><span class="grow">{!! $line('baccalaureate_school') !!}</span></div>
        <div class="spr-row">
            <span class="spr-label">Address of the school where the degree was taken:</span><span class="grow">{!! $line('baccalaureate_address') !!}</span>
            <span class="spr-label">Inclusive Years:</span><span style="width: 1.1in">{!! $line('baccalaureate_years') !!}</span>
        </div>
        <div class="spr-box">
            <p>Preferred Campus:
                {!! $choice('preferred_campus', 'main', 'Main Campus – Ampayon, Butuan City') !!}
                {!! $choice('preferred_campus', 'cabadbaran', 'Cabadbaran Campus') !!}
            </p>
            <p style="margin-top: 3px;"><strong>Desired Degree Programs to take/enroll in CSU:</strong></p>
            <div class="spr-row">
                <span class="spr-label">1st Priority:</span><span class="grow">{!! $line('priority_1') !!}</span>
                <span class="spr-label">2nd Priority:</span><span class="grow">{!! $line('priority_2') !!}</span>
                <span class="spr-label">3rd Priority:</span><span class="grow">{!! $line('priority_3') !!}</span>
            </div>
        </div>
        <p class="spr-center">STUDENT'S ADMISSION PLEDGE</p>
        <p class="spr-small">In consideration of my admission in CSU and of privileges of my admission as a student, I hereby promise and pledge to abide by and comply with all the rules and regulations laid down by competent authority(ies) in the University and in the college in which I am enrolled. Refusal to take this pledge or violation of its terms shall be sufficient cause for summary dismissal or denial of admission.</p>
        <p class="spr-center">DATA PRIVACY NOTICE</p>
        <p class="spr-small">This application for admission, in line with the Data Privacy Act of 2012, is committed to protecting and securing personal information obtained in the process of performance of its mandate. The personal and other information you provided (herein) will be processed and utilized solely for verification and identification purposes only. Collected personal information will be kept/stored and accessed only by the university and will not be shared with any outside parties unless written consent is secured. By affirming this, I agree to answer the following needed information with utmost willingness to take part in this application for admission.</p>
        @php
            $signImg = (string) old('signature_image', $raw('signature_image'));
            $signImg = str_starts_with($signImg, 'data:image/png;base64,') ? $signImg : '';
        @endphp
        @if($editing)
            <label class="choice" style="display: flex; margin-top: 6px; white-space: normal;">
                <input type="checkbox" name="certified" value="1" required {{ old('certified', isset($application) ? '1' : '') ? 'checked' : '' }}>
                I affirm the admission pledge and the data privacy notice. Sign in the box, then type your name below.
            </label>
            <div class="spr-pad">
                <canvas id="spr-signature" aria-label="Student signature"></canvas>
                <button type="button" id="spr-signature-clear">Clear signature</button>
                <input type="hidden" name="signature_image" id="spr-signature-input" value="{{ $signImg }}">
                <span class="spr-small">Sign with your finger or mouse. Your typed name stays under the signature.</span>
            </div>
            <div class="spr-row"><span class="grow">{!! $line('signature_name', ['required' => 'required']) !!}</span></div>
            <script>
                (function () {
                    var canvas = document.getElementById('spr-signature');
                    var input = document.getElementById('spr-signature-input');
                    var clearBtn = document.getElementById('spr-signature-clear');
                    var form = document.getElementById('spr-form');
                    if (!canvas || !input) return;
                    var required = {{ $studentMode ? 'true' : 'false' }};
                    var ctx = canvas.getContext('2d');
                    var drawing = false;
                    var signed = input.value !== '';
                    var stored = input.value;

                    function fit() {
                        var rect = canvas.getBoundingClientRect();
                        var ratio = Math.max(window.devicePixelRatio || 1, 1);
                        var snapshot = stored || (canvas.width > 0 && signed ? canvas.toDataURL('image/png') : '');
                        stored = '';
                        canvas.width = Math.max(1, Math.round(rect.width * ratio));
                        canvas.height = Math.max(1, Math.round(rect.height * ratio));
                        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                        ctx.lineWidth = 1.6;
                        ctx.lineCap = 'round';
                        ctx.lineJoin = 'round';
                        ctx.strokeStyle = '#111';
                        if (snapshot) {
                            var img = new Image();
                            img.onload = function () { ctx.drawImage(img, 0, 0, rect.width, rect.height); };
                            img.src = snapshot;
                        }
                    }

                    function point(event) {
                        var rect = canvas.getBoundingClientRect();
                        var src = event.touches ? event.touches[0] : event;
                        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
                    }

                    function start(event) {
                        drawing = true;
                        var p = point(event);
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        event.preventDefault();
                    }

                    function move(event) {
                        if (!drawing) return;
                        var p = point(event);
                        ctx.lineTo(p.x, p.y);
                        ctx.stroke();
                        signed = true;
                        event.preventDefault();
                    }

                    function end() {
                        if (!drawing) return;
                        drawing = false;
                        if (signed) input.value = canvas.toDataURL('image/png');
                    }

                    canvas.addEventListener('pointerdown', start);
                    canvas.addEventListener('pointermove', move);
                    canvas.addEventListener('pointerup', end);
                    canvas.addEventListener('pointerleave', end);
                    canvas.addEventListener('pointercancel', end);
                    clearBtn.addEventListener('click', function () {
                        signed = false;
                        stored = '';
                        input.value = '';
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                    });
                    if (form && required) {
                        form.addEventListener('submit', function (event) {
                            if (signed && input.value) return;
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            alert('Sign in the signature box before you save.');
                        }, true);
                    }
                    fit();
                    window.addEventListener('resize', fit);
                })();
            </script>
        @else
            @if($signImg)
                <img src="{{ $signImg }}" alt="Student signature" class="spr-sign-img">
            @endif
            <div class="spr-sign">{{ $raw('signature_name') }}<br>SIGNATURE OVER PRINTED NAME</div>
        @endif
        <p class="office-note">DO NOT WRITE ANYTHING BELOW THIS LINE</p>
        <div class="spr-row">
            <span class="spr-label">CSU-AT Result: Total Score:</span><span class="grow">{!! $editing && ! $studentMode ? $line('csu_at_total') : '<span class="fill">'.e($raw('csu_at_total')).'</span>' !!}</span>
            <span class="spr-label">English:</span><span class="grow">{!! $editing && ! $studentMode ? $line('csu_at_english') : '<span class="fill">'.e($raw('csu_at_english')).'</span>' !!}</span>
            <span class="spr-label">Math:</span><span class="grow">{!! $editing && ! $studentMode ? $line('csu_at_math') : '<span class="fill">'.e($raw('csu_at_math')).'</span>' !!}</span>
            <span class="spr-label">Science:</span><span class="grow">{!! $editing && ! $studentMode ? $line('csu_at_science') : '<span class="fill">'.e($raw('csu_at_science')).'</span>' !!}</span>
            <span class="spr-label">Logical Reasoning:</span><span class="grow">{!! $editing && ! $studentMode ? $line('csu_at_logical') : '<span class="fill">'.e($raw('csu_at_logical')).'</span>' !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Preferred Course: 1st Priority:</span><span class="grow">{!! $editing && ! $studentMode ? $line('office_course_1') : '<span class="fill">'.e($raw('office_course_1')).'</span>' !!}</span>
            <span class="spr-label">2nd Priority:</span><span class="grow">{!! $editing && ! $studentMode ? $line('office_course_2') : '<span class="fill">'.e($raw('office_course_2')).'</span>' !!}</span>
            <span class="spr-label">3rd Priority:</span><span class="grow">{!! $editing && ! $studentMode ? $line('office_course_3') : '<span class="fill">'.e($raw('office_course_3')).'</span>' !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Date of Admission Test:</span><span class="grow">{!! $editing && ! $studentMode ? $line('admission_test_date', ['type' => 'date']) : '<span class="fill">'.e($raw('admission_test_date')).'</span>' !!}</span>
        </div>
        <div class="spr-row">
            <span class="spr-label">Remarks: Qualified for Admission, valid for Academic Year</span>
            <span style="width: 1.2in">{!! $editing && ! $studentMode ? $line('admission_year') : '<span class="fill">'.e($raw('admission_year')).'</span>' !!}</span>
            <span>only.</span>
        </div>
        <div class="spr-footer">
            <div>F-ADM-001<br>Rev 01, 01/29/2024</div>
            <div style="text-align: center;">Reference: Model Code for State Colleges and Universities in Regions X, XI and XII. Chapter 47, page 49</div>
            <div style="text-align: center; font-weight: 700;">CHEELA MAE C. CASTRO<br><span style="font-weight: 400;">OAS, Head</span></div>
        </div>
    </section>
</div>
