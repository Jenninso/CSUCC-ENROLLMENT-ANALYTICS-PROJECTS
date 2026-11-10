<?php

namespace App\Http\Controllers;

use App\Models\RegistrationRequest;
use App\Models\SecretQuestion;
use App\Services\AccountService;
use App\Services\AuditLogger;
use App\Services\OtpService;
use App\Rules\PersonName;
use App\Support\PasswordPolicy;
use App\Support\ProfileFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register', [
            'questions' => SecretQuestion::query()->where('is_active', true)->orderBy('id')->get(),
            'extensions' => ProfileFields::EXTENSIONS,
            'sexes' => ProfileFields::SEXES,
        ]);
    }

    public function check(Request $request, AccountService $accounts)
    {
        $employeeId = strtoupper(trim((string) $request->query('employee_id', '')));
        $email = strtolower(trim((string) $request->query('email', '')));

        $result = [];
        if ($employeeId !== '') {
            $result['employee_id'] = $accounts->employeeIdAvailability($employeeId);
        }
        if ($email !== '') {
            $result['email'] = $accounts->emailAvailability($email);
        }

        return response()->json($result);
    }

    public function store(Request $request, AccountService $accounts, OtpService $otp)
    {
        $request->merge(['employee_id' => strtoupper(trim((string) $request->input('employee_id')))]);
        $validated = $this->validateRegistration($request, $accounts);

        $answers = [];
        foreach ([1, 2, 3] as $i) {
            $answers[] = [
                'question_id' => (int) $validated["question_{$i}"],
                'answer_hash' => $accounts->hashAnswer($validated["answer_{$i}"]),
            ];
        }

        $payload = array_merge([
            'employee_id' => strtoupper($validated['employee_id']),
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => strtolower($validated['email']),
            'password_hash' => $validated['password'],
            'secret_answers' => $answers,
        ], ProfileFields::extract($validated));

        $request->session()->put('registration', $payload);
        $code = $otp->issue($payload['email'], 'registration', null, $request);
        if ($code === '') {
            return redirect()->route('register.otp')->with('error', $otp->lastSendError ?: 'Please wait before requesting another code.');
        }

        $redirect = redirect()->route('register.otp')->with('otp_hint', $otp->revealForLocal($code));
        if ($otp->lastSendSucceeded) {
            return $redirect->with('success', 'An OTP was sent to your email. Check your inbox and spam folder.');
        }

        return $redirect->with('error', 'The OTP email could not be sent: '.($otp->lastSendError ?: 'SMTP is not configured in Settings.'));
    }

    public function otpForm(Request $request, OtpService $otp)
    {
        if (! $request->session()->has('registration')) {
            return redirect()->route('register');
        }

        $email = (string) $request->session()->get('registration')['email'];

        return view('auth.otp-verify', [
            'title' => 'Verify Your Account',
            'subtitle' => 'Enter the 6-digit verification code sent to your registered email.',
            'email' => $email,
            'maskedEmail' => $this->maskEmail($email),
            'verifyRoute' => route('register.otp'),
            'resendRoute' => route('register.otp.resend'),
            'submitLabel' => 'Verify OTP',
            'expiresIn' => $otp->expiresInSeconds($email, 'registration'),
            'resendCooldown' => $otp->resendCooldownSeconds($email, 'registration'),
        ]);
    }

    public function verifyOtp(Request $request, OtpService $otp, AuditLogger $audit)
    {
        $payload = $request->session()->get('registration');
        if (! $payload) {
            return redirect()->route('register');
        }

        $request->validate(['otp' => ['required', 'digits:6']]);

        $result = $otp->verifyOtp($payload['email'], 'registration', (string) $request->input('otp'), $request);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['otp' => $result['message'] ?? 'Invalid verification code.']);
        }

        $payload['password_hash'] = \Illuminate\Support\Facades\Hash::make($payload['password_hash']);

        RegistrationRequest::create([
            'employee_id' => $payload['employee_id'],
            'first_name' => $payload['first_name'],
            'middle_name' => $payload['middle_name'],
            'last_name' => $payload['last_name'],
            'extension_name' => $payload['extension_name'] ?? null,
            'birthdate' => $payload['birthdate'] ?? null,
            'sex' => $payload['sex'] ?? null,
            'email' => $payload['email'],
            'purok_street' => $payload['purok_street'] ?? null,
            'barangay' => $payload['barangay'] ?? null,
            'city_municipality' => $payload['city_municipality'] ?? null,
            'province' => $payload['province'] ?? null,
            'zipcode' => $payload['zipcode'] ?? null,
            'contact_number' => $payload['contact_number'] ?? null,
            'password_hash' => $payload['password_hash'],
            'secret_answers' => $payload['secret_answers'],
            'status' => 'pending',
            'otp_verified_at' => now(),
        ]);

        $request->session()->forget('registration');
        $audit->log('register', 'authentication', 'Registration submitted for approval: '.$payload['employee_id']);

        return redirect()->route('login')->with('success', 'Email verified. Registration submitted. A Super Administrator or Administrator must approve your account before you can sign in.');
    }

    public function resendOtp(Request $request, OtpService $otp)
    {
        $payload = $request->session()->get('registration');
        if (! $payload) {
            return redirect()->route('register');
        }

        $email = (string) $payload['email'];
        $result = $otp->resendOtp($email, 'registration', null, $request);
        $redirect = redirect()->route('register.otp')->with('otp_hint', $otp->revealForLocal($result['code'] ?? null));

        if (! ($result['ok'] ?? false)) {
            return $redirect->withErrors(['otp' => $result['message'] ?? 'Please wait before requesting another code.']);
        }

        if ($otp->lastSendSucceeded) {
            return $redirect->with('success', 'A new verification code was sent.');
        }

        return $redirect->with('error', 'The OTP email could not be sent: '.($otp->lastSendError ?: 'SMTP is not configured in Settings.'));
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

    private function validateRegistration(Request $request, AccountService $accounts): array
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail) use ($accounts) {
                    $status = $accounts->employeeIdAvailability((string) $value);
                    if (! $status['available']) {
                        $fail($status['message']);
                    }
                },
            ],
            'first_name' => ['required', 'string', 'max:100', new PersonName],
            'middle_name' => ['nullable', 'string', 'max:100', new PersonName(required: false)],
            'last_name' => ['required', 'string', 'max:100', new PersonName],
            ...ProfileFields::registrationRules(),
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
            'password' => [
                'required',
                'confirmed',
                PasswordPolicy::rule(),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_string($value) || ! PasswordPolicy::isAcceptable($value)) {
                        $fail(PasswordPolicy::message());
                    }
                },
            ],
            'question_1' => ['required', 'different:question_2', 'different:question_3', 'exists:secret_questions,id'],
            'question_2' => ['required', 'different:question_3', 'exists:secret_questions,id'],
            'question_3' => ['required', 'exists:secret_questions,id'],
            'answer_1' => ['required', 'string', 'max:255'],
            'answer_2' => ['required', 'string', 'max:255'],
            'answer_3' => ['required', 'string', 'max:255'],
        ]);

        return $validated;
    }
}
