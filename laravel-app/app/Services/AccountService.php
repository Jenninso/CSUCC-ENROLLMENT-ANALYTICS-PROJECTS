<?php

namespace App\Services;

use App\Mail\AccountCredentialsMail;
use App\Models\RegistrationRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSecretAnswer;
use App\Support\AccountCategory;
use App\Support\ProfileFields;
use App\Support\Username;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AccountService
{
    public function createUser(array $data, ?array $secretAnswers = null, ?array $permissionIds = null): User
    {
        return DB::transaction(function () use ($data, $secretAnswers, $permissionIds) {
            $user = new User([
                'employee_id' => strtoupper(trim((string) $data['employee_id'])),
                'first_name' => $data['first_name'] ?? null,
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'extension_name' => $data['extension_name'] ?? null,
                'birthdate' => $data['birthdate'] ?? null,
                'sex' => $data['sex'] ?? null,
                'address' => $data['address'] ?? null,
                'purok_street' => $data['purok_street'] ?? null,
                'barangay' => $data['barangay'] ?? null,
                'city_municipality' => $data['city_municipality'] ?? null,
                'province' => $data['province'] ?? null,
                'zipcode' => $data['zipcode'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'name' => User::composeName($data['first_name'] ?? null, $data['middle_name'] ?? null, $data['last_name'] ?? null, $data['extension_name'] ?? null) ?: strtolower($data['email']),
                'email' => strtolower($data['email']),
                'username' => Username::normalize($data['username'] ?? Username::fromEmail((string) $data['email'])),
                'role_id' => $data['role_id'],
                'status' => $data['status'] ?? 'active',
                'must_change_password' => $data['must_change_password'] ?? false,
                'credentials_completed_at' => $data['credentials_completed_at'] ?? null,
            ]);
            $user->password = $data['password'];
            $user->save();

            if (Hash::isHashed($data['password']) && $user->password !== $data['password']) {
                DB::table('users')->where('id', $user->id)->update(['password' => $data['password']]);
                $user->refresh();
            }

            if ($secretAnswers) {
                foreach ($secretAnswers as $row) {
                    UserSecretAnswer::create([
                        'user_id' => $user->id,
                        'secret_question_id' => $row['question_id'],
                        'answer_hash' => $row['answer_hash'] ?? Hash::make($this->normalizeAnswer($row['answer'])),
                    ]);
                }
            }

            if ($permissionIds !== null) {
                $user->permissions()->sync($permissionIds);
            }

            return $user;
        });
    }

    public function approveRegistration(RegistrationRequest $request, User $reviewer): User
    {
        $role = Role::query()->where('role_name', 'User')->firstOrFail();

        $user = $this->createUser([
            'employee_id' => $request->employee_id,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'extension_name' => $request->extension_name,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'purok_street' => $request->purok_street,
            'barangay' => $request->barangay,
            'city_municipality' => $request->city_municipality,
            'province' => $request->province,
            'zipcode' => $request->zipcode,
            'contact_number' => $request->contact_number,
            'address' => ProfileFields::composeAddress(
                $request->purok_street,
                $request->barangay,
                $request->city_municipality,
                $request->province,
                $request->zipcode
            ),
            'email' => $request->email,
            'password' => $request->password_hash,
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
        ], collect($request->secret_answers)->map(fn ($row) => [
            'question_id' => $row['question_id'],
            'answer_hash' => $row['answer_hash'],
        ])->all());

        $request->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $user;
    }

    public function normalizeAnswer(string $answer): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $answer) ?? $answer));
    }

    public function hashAnswer(string $answer): string
    {
        return Hash::make($this->normalizeAnswer($answer));
    }

    public function checkAnswer(string $plain, string $hash): bool
    {
        return Hash::check($this->normalizeAnswer($plain), $hash);
    }

    public function snapshot(User $user): array
    {
        return [
            'employee_id' => $user->employee_id,
            'full_name' => $user->fullName(),
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'account_category' => $user->accountCategoryLabel(),
            'role' => $user->roleName(),
            'status' => $user->status,
            'address' => $user->address,
        ];
    }

    public function employeeIdAvailability(string $employeeId): array
    {
        $employeeId = strtoupper(trim($employeeId));

        if ($employeeId === '') {
            return [
                'available' => false,
                'reason' => 'empty',
                'message' => 'Enter an Employee ID.',
            ];
        }

        if (User::query()->where('employee_id', $employeeId)->exists()) {
            return [
                'available' => false,
                'reason' => 'taken',
                'message' => 'This Employee ID is already in use. Another account already uses it, so it cannot be used again.',
            ];
        }

        $pending = RegistrationRequest::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return [
                'available' => false,
                'reason' => 'pending',
                'message' => 'This Employee ID is already used in a registration that is still pending approval.',
            ];
        }

        return [
            'available' => true,
            'reason' => null,
            'message' => 'This Employee ID is available.',
        ];
    }

    public function emailAvailability(string $email): array
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return [
                'available' => false,
                'reason' => 'empty',
                'message' => 'Enter an email address.',
                'account_category' => AccountCategory::PERSONAL,
                'account_category_label' => AccountCategory::LABELS[AccountCategory::PERSONAL],
            ];
        }

        $category = AccountCategory::fromEmail($email);
        $categoryMeta = [
            'account_category' => $category,
            'account_category_label' => AccountCategory::LABELS[$category],
        ];

        if (User::query()->where('email', $email)->exists()) {
            return [
                'available' => false,
                'reason' => 'taken',
                'message' => 'This email is already in use. Another account already uses it, so it cannot be used again.',
                ...$categoryMeta,
            ];
        }

        $pending = RegistrationRequest::query()
            ->where('email', $email)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return [
                'available' => false,
                'reason' => 'pending',
                'message' => 'This email is already used in a registration that is still pending approval.',
                ...$categoryMeta,
            ];
        }

        return [
            'available' => true,
            'reason' => null,
            'message' => 'This email is available.',
            ...$categoryMeta,
        ];
    }

    public function nextEmployeeId(): string
    {
        $ids = User::query()->pluck('employee_id')
            ->merge(RegistrationRequest::query()->pluck('employee_id'));

        $max = 0;
        foreach ($ids as $id) {
            if (preg_match('/(\d+)/', (string) $id, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'EMP-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    public function confirmSuperAdminDeletion(mixed $typedEmployeeId, User $user): true|string
    {
        if (! $user->isSuperAdmin()) {
            return true;
        }

        $typed = strtoupper(trim((string) $typedEmployeeId));
        $expected = strtoupper(trim((string) $user->employee_id));

        if ($typed === '' || $expected === '' || $typed !== $expected) {
            return 'Type the Employee ID '.$user->employee_id.' exactly to confirm deletion of this Super Administrator.';
        }

        return true;
    }

    /**
     * Re-authenticate the acting staff member before delete / delete-request actions.
     */
    public function confirmActorCredentials(User $actor, mixed $email, mixed $password): true|array
    {
        $email = strtolower(trim((string) $email));
        $password = (string) $password;
        $errors = [];

        if ($email === '' || strtolower((string) $actor->email) !== $email) {
            $errors['confirm_email'] = 'Enter your account email exactly to confirm.';
        }

        if ($password === '' || ! Hash::check($password, $actor->getAuthPassword())) {
            $errors['confirm_password'] = 'Incorrect password. Enter your current account password to confirm.';
        }

        return $errors === [] ? true : $errors;
    }

    public function isLastActiveSuperAdmin(User $user): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        return ! User::query()
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Super Administrator'))
            ->exists();
    }

    public function deleteUser(User $user): void
    {
        $user->auditLogs()->update(['user_id' => null]);

        RegistrationRequest::query()
            ->where(function ($query) use ($user) {
                $query->where('employee_id', $user->employee_id)
                    ->orWhere('email', $user->email);
            })
            ->delete();

        $user->delete();
    }

    public function deactivatePreviousSuperAdmins(User $current): int
    {
        $superRoleId = Role::query()->where('role_name', 'Super Administrator')->value('id');
        if (! $superRoleId) {
            return 0;
        }

        return User::query()
            ->where('role_id', $superRoleId)
            ->where('id', '!=', $current->id)
            ->where('status', '!=', 'inactive')
            ->update(['status' => 'inactive']);
    }

    /**
     * @return true|string Error message when activation is not allowed.
     */
    public function activateUser(User $user): true|string
    {
        if ($user->isActive()) {
            return 'This account is already active.';
        }

        if ($user->isBlocked()) {
            return 'Unblock this account before activating it.';
        }

        $user->update([
            'status' => 'active',
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);

        if ($user->isSuperAdmin()) {
            $this->deactivatePreviousSuperAdmins($user);
        }

        return true;
    }

    /**
     * @return true|string Error message when deactivation is not allowed.
     */
    public function deactivateUser(User $user): true|string
    {
        if ($this->isLastActiveSuperAdmin($user)) {
            return 'Cannot deactivate the last active Super Administrator.';
        }

        if ($user->isInactive()) {
            return 'This account is already deactivated.';
        }

        $user->update(['status' => 'inactive']);

        return true;
    }

    /**
     * Email the new account that their role was assigned, including username + temporary password.
     */
    public function notifyRoleAssigned(User $user, string $plainPassword, ?string $roleName = null): bool
    {
        $roleName = $roleName ?: ($user->roleName() ?: 'User');
        $email = strtolower(trim((string) $user->email));
        $mailable = new AccountCredentialsMail(
            $roleName,
            $email,
            $plainPassword,
            (string) $user->employee_id,
            (string) $user->username,
        );

        try {
            if (app()->environment('testing')) {
                Mail::mailer('array')->to($email)->send($mailable);

                return true;
            }

            $settings = app(MailSettings::class);
            if (! $settings->isConfigured()) {
                throw new \RuntimeException('SMTP is not configured. Open Settings and save the Gmail address and App Password used to send account emails.');
            }

            $settings->apply(true);
            try {
                Mail::to($email)->send($mailable);
            } catch (\Throwable $e) {
                if (! $this->isCertificateError($e)) {
                    throw $e;
                }

                Log::warning('Account credentials SMTP certificate check failed, retrying without peer verification: '.$e->getMessage());
                $settings->apply(false);
                Mail::to($email)->send($mailable);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Account credentials email failed: '.$e->getMessage());

            return false;
        }
    }

    private function isCertificateError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'certificate')
            || str_contains($message, 'ssl')
            || str_contains($message, 'peer');
    }
}
