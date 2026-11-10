<?php

namespace App\Http\Controllers;

use App\Models\SecretQuestion;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AuditLogger;
use App\Services\OtpService;
use App\Support\PasswordPolicy;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function create()
    {
        return view('auth.forgot');
    }

    public function lookup(Request $request)
    {
        $employeeId = strtoupper(trim((string) $request->query('employee_id', $request->input('employee_id'))));
        if ($employeeId === '') {
            return response()->json(['found' => false]);
        }

        $user = User::query()->where('employee_id', $employeeId)->first();
        if (! $user || ! $user->isActive()) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'employee_id' => $user->employee_id,
            'name' => $user->fullName() ?: $user->email,
            'role' => $user->roleName(),
        ]);
    }

    public function identify(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string'],
        ]);

        $user = User::query()->where('employee_id', strtoupper(trim($validated['employee_id'])))->first();

        if (! $user || $user->secretAnswers()->count() < 3) {
            return back()->withErrors(['employee_id' => 'No recoverable account was found for that Employee ID.'])->withInput();
        }

        if ($user->isBlocked()) {
            return back()->withErrors(['employee_id' => 'This account is blocked and cannot recover a password.'])->withInput();
        }

        if ($user->isInactive() || ! $user->isActive()) {
            return back()->withErrors(['employee_id' => 'This account is deactivated and cannot recover a password. Ask a Super Administrator to activate it.'])->withInput();
        }

        $request->session()->put('password_reset', [
            'user_id' => $user->id,
            'question_order' => $this->shuffledQuestionIds(),
        ]);

        return redirect()->route('password.questions');
    }

    public function questions(Request $request, OtpService $otp)
    {
        $user = $this->userFromSession($request);
        if (! $user) {
            return redirect()->route('password.request');
        }

        $questions = $this->catalogForReset($request);
        $otpSent = (bool) $request->session()->get('password_reset.otp_sent');

        if ($otpSent) {
            return view('auth.otp-verify', [
                'title' => 'Verify Your Account',
                'subtitle' => 'Enter the 6-digit verification code sent to your registered email.',
                'email' => $user->email,
                'maskedEmail' => $this->maskEmail($user->email),
                'verifyRoute' => route('password.questions'),
                'resendRoute' => route('password.otp.resend'),
                'submitLabel' => 'Verify OTP',
                'expiresIn' => $otp->expiresInSeconds($user->email, 'password_reset'),
                'resendCooldown' => $otp->resendCooldownSeconds($user->email, 'password_reset'),
            ]);
        }

        return view('auth.forgot-questions', compact('user', 'questions', 'otpSent'));
    }

    public function verifyQuestions(Request $request, AccountService $accounts, OtpService $otp)
    {
        $user = $this->userFromSession($request);
        if (! $user) {
            return redirect()->route('password.request');
        }

        if ($request->session()->get('password_reset.otp_sent')) {
            return $this->verifyOtp($request, $otp);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'question_1' => ['required', 'different:question_2', 'different:question_3', 'exists:secret_questions,id'],
            'question_2' => ['required', 'different:question_3', 'exists:secret_questions,id'],
            'question_3' => ['required', 'exists:secret_questions,id'],
            'answer_1' => ['required', 'string'],
            'answer_2' => ['required', 'string'],
            'answer_3' => ['required', 'string'],
        ]);

        if (strcasecmp($validated['email'], $user->email) !== 0) {
            return back()->withErrors(['email' => 'That email does not match this Employee ID.'])->withInput();
        }

        $stored = $user->secretAnswers()->get()->keyBy('secret_question_id');
        $correct = 0;
        foreach ([1, 2, 3] as $slot) {
            $row = $stored->get((int) $validated["question_{$slot}"]);
            if ($row && $accounts->checkAnswer($validated["answer_{$slot}"], $row->answer_hash)) {
                $correct++;
            }
        }

        if ($correct < 2) {
            return back()->withErrors(['answer' => 'Select the three questions you saved and enter at least two correct answers.'])->withInput();
        }

        $code = $otp->issue($user->email, 'password_reset', $user, $request);
        if ($code === '') {
            return back()->withErrors(['email' => $otp->lastSendError ?: 'Please wait before requesting another code.']);
        }

        $request->session()->put('password_reset.questions_ok', true);
        $request->session()->put('password_reset.otp_sent', true);

        $redirect = redirect()->route('password.questions')->with('otp_hint', $otp->revealForLocal($code));
        if ($otp->lastSendSucceeded) {
            return $redirect->with('success', 'OTP sent to your email. Check your inbox and spam folder, then enter the code.');
        }

        return $redirect->with('error', 'The OTP email could not be sent: '.($otp->lastSendError ?: 'SMTP is not configured in Settings.'));
    }

    public function verifyOtp(Request $request, OtpService $otp)
    {
        $user = $this->userFromSession($request);
        if (! $user || ! $request->session()->get('password_reset.questions_ok')) {
            return redirect()->route('password.request');
        }

        $request->validate(['otp' => ['required', 'digits:6']]);
        $result = $otp->verifyOtp($user->email, 'password_reset', (string) $request->input('otp'), $request, $user);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['otp' => $result['message'] ?? 'Invalid verification code.']);
        }

        $request->session()->put('password_reset.otp_ok', true);

        return redirect()->route('password.reset');
    }

    public function resendOtp(Request $request, OtpService $otp)
    {
        $user = $this->userFromSession($request);
        if (! $user || ! $request->session()->get('password_reset.questions_ok')) {
            return redirect()->route('password.request');
        }

        $result = $otp->resendOtp($user->email, 'password_reset', $user, $request);
        $redirect = redirect()->route('password.questions')->with('otp_hint', $otp->revealForLocal($result['code'] ?? null));

        if (! ($result['ok'] ?? false)) {
            return $redirect->withErrors(['otp' => $result['message'] ?? 'Please wait before requesting another code.']);
        }

        if ($otp->lastSendSucceeded) {
            return $redirect->with('success', 'A new verification code was sent.');
        }

        return $redirect->with('error', 'The OTP email could not be sent: '.($otp->lastSendError ?: 'SMTP is not configured in Settings.'));
    }

    public function resetForm(Request $request)
    {
        if (! $request->session()->get('password_reset.otp_ok')) {
            return redirect()->route('password.request');
        }

        $user = $this->userFromSession($request);

        return view('auth.reset-password', compact('user'));
    }

    public function reset(Request $request, AuditLogger $audit, OtpService $otp)
    {
        $user = $this->userFromSession($request);
        if (! $user || ! $request->session()->get('password_reset.otp_ok')) {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
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
        ]);

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);
        $otp->invalidateOtp($user->email, 'password_reset');
        $request->session()->forget('password_reset');
        $audit->log('password_reset', 'authentication', 'Reset password using Employee ID, security questions, and email OTP.', null, $user);

        return redirect()->route('login')->with('success', 'Password updated. You may now sign in.');
    }

    private function userFromSession(Request $request): ?User
    {
        $id = $request->session()->get('password_reset.user_id');
        if (! $id) {
            return null;
        }

        $user = User::with('secretAnswers')->find($id);
        if (! $user || ! $user->isActive()) {
            $request->session()->forget('password_reset');

            return null;
        }

        return $user;
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

    /**
     * @return list<int>
     */
    private function shuffledQuestionIds(): array
    {
        return SecretQuestion::query()->where('is_active', true)->pluck('id')->shuffle()->values()->all();
    }

    private function catalogForReset(Request $request)
    {
        $ids = $request->session()->get('password_reset.question_order');
        $questions = SecretQuestion::query()->where('is_active', true)->get();
        if (! is_array($ids) || $ids === []) {
            $ids = $questions->pluck('id')->shuffle()->values()->all();
            $request->session()->put('password_reset.question_order', $ids);
        }

        return $questions
            ->sortBy(fn ($question) => array_search($question->id, array_map('intval', $ids), true))
            ->values();
    }
}
