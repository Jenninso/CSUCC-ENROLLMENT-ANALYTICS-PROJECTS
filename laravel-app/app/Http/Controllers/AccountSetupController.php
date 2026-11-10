<?php

namespace App\Http\Controllers;

use App\Models\SecretQuestion;
use App\Rules\PersonName;
use App\Services\AccountService;
use App\Services\AuditLogger;
use App\Support\PasswordPolicy;
use Illuminate\Http\Request;

class AccountSetupController extends Controller
{
    public function passwordForm()
    {
        $user = auth()->user();
        if (! $user->mustChangePassword()) {
            return redirect()->route($user->hasCompletedCredentials() ? 'dashboard' : 'setup.credentials');
        }

        return view('auth.setup-password');
    }

    public function updatePassword(Request $request, AuditLogger $audit)
    {
        $user = $request->user();
        abort_unless($user->mustChangePassword(), 403);

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

        $audit->log('password_change', 'authentication', 'Changed the temporary password.', $request, $user);

        return redirect()->route('setup.credentials')->with('success', 'Password updated. Complete your credentials to continue.');
    }

    public function credentialsForm()
    {
        $user = auth()->user();
        if ($user->mustChangePassword()) {
            return redirect()->route('setup.password');
        }
        if ($user->hasCompletedCredentials()) {
            return redirect()->route('dashboard');
        }

        return view('auth.setup-credentials', [
            'user' => $user,
            'questions' => SecretQuestion::query()->where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    public function storeCredentials(Request $request, AccountService $accounts, AuditLogger $audit)
    {
        $user = $request->user();
        abort_if($user->mustChangePassword(), 403);
        abort_if($user->hasCompletedCredentials(), 403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100', new PersonName],
            'middle_name' => ['nullable', 'string', 'max:100', new PersonName(required: false)],
            'last_name' => ['required', 'string', 'max:100', new PersonName],
            'address' => ['nullable', 'string', 'max:255'],
            'question_1' => ['required', 'different:question_2', 'different:question_3', 'exists:secret_questions,id'],
            'question_2' => ['required', 'different:question_3', 'exists:secret_questions,id'],
            'question_3' => ['required', 'exists:secret_questions,id'],
            'answer_1' => ['required', 'string', 'max:255'],
            'answer_2' => ['required', 'string', 'max:255'],
            'answer_3' => ['required', 'string', 'max:255'],
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'address' => $validated['address'] ?? null,
            'name' => \App\Models\User::composeName($validated['first_name'], $validated['middle_name'] ?? null, $validated['last_name']),
            'credentials_completed_at' => now(),
        ]);

        $user->secretAnswers()->delete();
        foreach ([1, 2, 3] as $i) {
            $user->secretAnswers()->create([
                'secret_question_id' => $validated["question_{$i}"],
                'answer_hash' => $accounts->hashAnswer($validated["answer_{$i}"]),
            ]);
        }

        $audit->log('credentials_setup', 'authentication', 'Completed account credentials and security questions.', $request, $user->fresh());

        return redirect()->route('dashboard')->with('success', 'Credentials saved. You can now use the system.');
    }
}
