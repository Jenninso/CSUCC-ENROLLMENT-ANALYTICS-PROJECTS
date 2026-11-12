<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SecretQuestion;
use App\Models\SpesApplication;
use App\Models\StudentRecord;
use App\Models\StudentStatusReason;
use App\Rules\PersonName;
use App\Services\AccountService;
use App\Services\AuditLogger;
use App\Services\OtpService;
use App\Support\PasswordPolicy;
use App\Support\Username;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentAccountController extends Controller
{
    public function create()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->isStudent() ? 'student.home' : 'dashboard');
        }

        return view('student.register', [
            'questions' => SecretQuestion::query()->where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, AccountService $accounts, OtpService $otp)
    {
        $validated = $this->validateRegistration($request, $accounts);
        $answers = [];
        foreach ([1, 2, 3] as $i) {
            $answers[] = [
                'question_id' => (int) $validated["question_{$i}"],
                'answer_hash' => $accounts->hashAnswer($validated["answer_{$i}"]),
            ];
        }

        $request->session()->put('student_registration', [
            'student_id' => strtoupper($validated['student_id']),
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],
            'secret_answers' => $answers,
        ]);

        $email = strtolower($validated['email']);
        $code = $otp->issue($email, 'student_registration', null, $request);
        if ($code === '') {
            return redirect()->route('student.register.otp')->with('error', $otp->lastSendError ?: 'Please wait before requesting another code.');
        }

        $redirect = redirect()->route('student.register.otp')->with('otp_hint', $otp->revealForLocal($code));
        if ($otp->lastSendSucceeded) {
            return $redirect->with('success', 'An OTP was sent to your email. Check your inbox and spam folder.');
        }

        return $redirect->with('error', 'The OTP email could not be sent: '.($otp->lastSendError ?: 'SMTP is not configured. Use the code shown on the next page if this is a local setup.'));
    }

    public function otpForm(Request $request, OtpService $otp)
    {
        $payload = $request->session()->get('student_registration');
        if (! is_array($payload) || empty($payload['email'])) {
            return redirect()->route('student.register');
        }

        $email = (string) $payload['email'];

        return view('auth.otp-verify', [
            'title' => 'Verify Your Student Account',
            'subtitle' => 'Enter the 6-digit code sent to your email. After this, you can sign in with your Student ID.',
            'email' => $email,
            'maskedEmail' => $this->maskEmail($email),
            'verifyRoute' => route('student.register.otp'),
            'resendRoute' => route('student.register.otp.resend'),
            'submitLabel' => 'Create student account',
            'expiresIn' => $otp->expiresInSeconds($email, 'student_registration'),
            'resendCooldown' => $otp->resendCooldownSeconds($email, 'student_registration'),
        ]);
    }

    public function verifyOtp(Request $request, OtpService $otp, AccountService $accounts, AuditLogger $audit)
    {
        $payload = $request->session()->get('student_registration');
        if (! is_array($payload)) {
            return redirect()->route('student.register');
        }

        $request->validate(['otp' => ['required', 'digits:6']]);
        $result = $otp->verifyOtp($payload['email'], 'student_registration', (string) $request->input('otp'), $request);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['otp' => $result['message'] ?? 'Invalid verification code.']);
        }

        $role = Role::query()->where('role_name', 'Student')->firstOrFail();
        $studentId = strtoupper($payload['student_id']);
        $user = $accounts->createUser([
            'employee_id' => $studentId,
            'username' => Username::normalize($studentId),
            'first_name' => $payload['first_name'],
            'middle_name' => $payload['middle_name'],
            'last_name' => $payload['last_name'],
            'email' => $payload['email'],
            'password' => $payload['password'],
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ], $payload['secret_answers']);

        $request->session()->forget('student_registration');
        $audit->log('register', 'student', 'Student account created for '.$studentId.'.', $request, $user);

        return redirect()->route('login')->with('success', 'Student account created. Sign in with your Student ID as the username.');
    }

    public function resendOtp(Request $request, OtpService $otp)
    {
        $payload = $request->session()->get('student_registration');
        if (! is_array($payload)) {
            return redirect()->route('student.register');
        }

        $email = (string) $payload['email'];
        $result = $otp->resendOtp($email, 'student_registration', null, $request);
        $redirect = redirect()->route('student.register.otp')->with('otp_hint', $otp->revealForLocal($result['code'] ?? null));
        if (! ($result['ok'] ?? false)) {
            return $redirect->withErrors(['otp' => $result['message'] ?? 'Please wait before requesting another code.']);
        }

        if ($otp->lastSendSucceeded) {
            return $redirect->with('success', 'A new verification code was sent.');
        }

        return $redirect->with('error', 'The OTP email could not be sent: '.($otp->lastSendError ?: 'SMTP is not configured.'));
    }

    public function home(Request $request)
    {
        $user = $request->user();
        $studentId = $user->studentNumber();

        return view('student.home', [
            'studentId' => $studentId,
            'record' => StudentRecord::query()->with('program')->where('student_id', $studentId)->latest('id')->first(),
            'spesCount' => SpesApplication::query()->where('user_id', $user->id)->count(),
            'reasonCount' => StudentStatusReason::query()->where('user_id', $user->id)->count(),
        ]);
    }

    private function validateRegistration(Request $request, AccountService $accounts): array
    {
        $request->merge([
            'student_id' => strtoupper(trim((string) $request->input('student_id'))),
        ]);

        return $request->validate([
            'student_id' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[A-Z0-9][A-Z0-9-]{1,28}[A-Z0-9]$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($accounts) {
                    if (! StudentRecord::query()->where('student_id', $value)->exists()) {
                        $fail('No enrollment record uses this Student ID. Ask the registrar if the ID is correct.');

                        return;
                    }
                    $status = $accounts->employeeIdAvailability((string) $value);
                    if (! $status['available']) {
                        $fail('A student account already exists for this Student ID.');
                    }
                },
            ],
            'first_name' => ['required', 'string', 'max:100', new PersonName],
            'middle_name' => ['nullable', 'string', 'max:100', new PersonName(required: false)],
            'last_name' => ['required', 'string', 'max:100', new PersonName],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($accounts) {
                    $status = $accounts->emailAvailability((string) $value);
                    if (! $status['available']) {
                        $fail($status['message']);
                    }
                },
            ],
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
            'question_1' => ['required', 'different:question_2', 'different:question_3', 'exists:secret_questions,id'],
            'question_2' => ['required', 'different:question_3', 'exists:secret_questions,id'],
            'question_3' => ['required', 'exists:secret_questions,id'],
            'answer_1' => ['required', 'string', 'max:255'],
            'answer_2' => ['required', 'string', 'max:255'],
            'answer_3' => ['required', 'string', 'max:255'],
        ]);
    }

    private function maskEmail(string $email): string
    {
        $email = strtolower(trim($email));
        $at = strpos($email, '@');
        if ($at === false) {
            return 'your email';
        }
        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);
        $visible = max(1, min(2, strlen($local)));

        return substr($local, 0, $visible).str_repeat('*', max(1, strlen($local) - $visible)).'@'.$domain;
    }
}
