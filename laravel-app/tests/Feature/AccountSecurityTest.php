<?php

namespace Tests\Feature;

use App\Mail\AccountCredentialsMail;
use App\Models\RegistrationRequest;
use App\Models\Role;
use App\Models\SecretQuestion;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_stays_pending_until_approved(): void
    {
        $this->seedRolesAndQuestions();
        $super = $this->makeUser('Super Administrator', 'super@example.com');

        $response = $this->post('/register', $this->registrationPayload());
        $response->assertRedirect(route('register.otp'));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('users', ['email' => 'new.staff@example.com']);

        $code = $response->getSession()->get('otp_hint');
        $this->assertNotEmpty($code);
        $this->post('/register/otp', ['otp' => $code])->assertRedirect(route('login'));

        $this->assertDatabaseHas('registration_requests', [
            'email' => 'new.staff@example.com',
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'new.staff@example.com']);

        $request = RegistrationRequest::query()->first();
        $this->actingAs($super)->post(route('approvals.approve', $request))->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'new.staff@example.com', 'employee_id' => 'EMP-7777']);
    }

    public function test_forgot_password_requires_two_of_three_secret_answers(): void
    {
        $this->seedRolesAndQuestions();
        $user = $this->makeUser('User', 'staff@example.com');
        $questions = SecretQuestion::query()->orderBy('id')->limit(3)->get();
        $accounts = app(AccountService::class);
        foreach ($questions as $index => $question) {
            $user->secretAnswers()->create([
                'secret_question_id' => $question->id,
                'answer_hash' => $accounts->hashAnswer('answer-'.$index),
            ]);
        }

        $this->post('/forgot-password', ['employee_id' => $user->employee_id])->assertRedirect(route('password.questions'));
        $page = $this->get(route('password.questions'))->assertOk();
        $page->assertSee('Select a question')
            ->assertSee('Question 1 of 3')
            ->assertSee('Question 3 of 3');
        foreach (config('secret_questions') as $prompt) {
            $page->assertSee($prompt);
        }

        $this->post('/forgot-password/questions', [
            'email' => 'staff@example.com',
            'question_1' => $questions[0]->id,
            'question_2' => $questions[1]->id,
            'question_3' => $questions[2]->id,
            'answer_1' => 'wrong',
            'answer_2' => 'answer-1',
            'answer_3' => 'answer-2',
        ])->assertRedirect(route('password.questions'));
        $this->assertTrue(session('password_reset.otp_sent'));

        $this->post('/forgot-password', ['employee_id' => $user->employee_id]);
        $this->post('/forgot-password/questions', [
            'email' => 'staff@example.com',
            'question_1' => $questions[0]->id,
            'question_2' => $questions[1]->id,
            'question_3' => $questions[2]->id,
            'answer_1' => 'no',
            'answer_2' => 'nope',
            'answer_3' => 'no',
        ])->assertSessionHasErrors('answer');
    }

    public function test_register_page_offers_nine_questions_and_three_answer_slots(): void
    {
        $this->seedRolesAndQuestions();

        $html = $this->get(route('register'))->assertOk()->getContent();
        $this->assertCount(9, config('secret_questions'));
        $this->assertSame(9, SecretQuestion::query()->count());
        foreach (config('secret_questions') as $question) {
            $this->assertSame(3, substr_count($html, e($question)), $question.' should appear in each of the 3 selectors');
        }
        $this->assertStringContainsString('Question 1 of 3', $html);
        $this->assertStringContainsString('Question 3 of 3', $html);
    }

    public function test_forgot_password_lookup_returns_name_for_employee_id(): void
    {
        $this->seedRolesAndQuestions();
        $user = $this->makeUser('Administrator', 'admin-lookup@example.com');

        $this->get('/forgot-password/lookup?employee_id='.$user->employee_id)
            ->assertOk()
            ->assertJsonFragment(['found' => true, 'employee_id' => $user->employee_id]);
    }

    public function test_creating_super_admin_deactivates_previous(): void
    {
        $this->seedRolesAndQuestions();
        $current = $this->makeUser('Super Administrator', 'old-super@example.com');
        $superRole = Role::query()->where('role_name', 'Super Administrator')->first();

        $this->actingAs($current)->post('/users', [
            'username' => 'new.super',
            'email' => 'new-super@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role_id' => $superRole->id,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['email' => 'old-super@example.com', 'status' => 'inactive']);
        $this->assertDatabaseHas('users', ['email' => 'new-super@example.com', 'username' => 'new.super', 'status' => 'active']);

        $this->post('/login', [
            'username' => 'old-super',
            'password' => 'password',
        ])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_use_forgot_password(): void
    {
        $this->seedRolesAndQuestions();
        $user = $this->makeUser('User', 'inactive-reset@example.com');
        $accounts = app(AccountService::class);
        foreach (SecretQuestion::query()->orderBy('id')->limit(3)->get() as $index => $question) {
            $user->secretAnswers()->create([
                'secret_question_id' => $question->id,
                'answer_hash' => $accounts->hashAnswer('answer-'.$index),
            ]);
        }
        $user->update(['status' => 'inactive']);

        $this->get('/forgot-password/lookup?employee_id='.$user->employee_id)
            ->assertOk()
            ->assertJsonFragment(['found' => false]);

        $this->from(route('password.request'))
            ->post('/forgot-password', ['employee_id' => $user->employee_id])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('employee_id');
    }

    public function test_super_admin_can_activate_inactive_super_admin_and_deactivates_previous(): void
    {
        $this->seedRolesAndQuestions();
        $active = $this->makeUser('Super Administrator', 'active-super@example.com');
        $inactive = $this->makeUser('Super Administrator', 'inactive-super@example.com');
        $inactive->update(['status' => 'inactive']);

        $this->actingAs($active)
            ->post(route('users.activate', $inactive))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'inactive-super@example.com', 'status' => 'active']);
        $this->assertDatabaseHas('users', ['email' => 'active-super@example.com', 'status' => 'inactive']);
    }

    public function test_cannot_deactivate_last_active_super_admin(): void
    {
        $this->seedRolesAndQuestions();
        $only = $this->makeUser('Super Administrator', 'only-super@example.com');
        $accounts = app(AccountService::class);

        $this->assertSame(
            'Cannot deactivate the last active Super Administrator.',
            $accounts->deactivateUser($only)
        );
        $this->assertDatabaseHas('users', ['email' => 'only-super@example.com', 'status' => 'active']);
    }

    public function test_login_sets_online_presence_and_logout_sets_offline(): void
    {
        $this->seedRolesAndQuestions();
        $user = $this->makeUser('User', 'presence@example.com');

        $this->assertFalse($user->fresh()->isOnline());

        $this->post('/login', [
            'username' => 'presence',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->isOnline());
        $this->assertSame('Online', $user->fresh()->presenceLabel());

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('popstate', false);
        $this->assertFalse($user->fresh()->isOnline());
        $this->assertSame('Offline', $user->fresh()->presenceLabel());
    }

    public function test_creating_admin_roles_emails_credentials(): void
    {
        Mail::fake();
        $this->seedRolesAndQuestions();
        $current = $this->makeUser('Super Administrator', 'super-mail@example.com');

        foreach (['Administrator', 'User'] as $roleName) {
            $role = Role::query()->where('role_name', $roleName)->firstOrFail();
            $email = strtolower($roleName).'-mail@example.com';
            $tempPassword = 'TempPass1!';

            $this->actingAs($current)->post('/users', [
                'username' => strtolower($roleName).'.mail',
                'email' => $email,
                'password' => $tempPassword,
                'password_confirmation' => $tempPassword,
                'role_id' => $role->id,
            ])->assertRedirect(route('users.index'));

            Mail::assertSent(AccountCredentialsMail::class, function (AccountCredentialsMail $mail) use ($email, $tempPassword, $roleName) {
                return $mail->hasTo($email)
                    && $mail->email === $email
                    && $mail->username === strtolower($roleName).'.mail'
                    && $mail->temporaryPassword === $tempPassword
                    && $mail->roleName === $roleName;
            });
        }
    }

    public function test_creating_super_admin_emails_credentials(): void
    {
        Mail::fake();
        $this->seedRolesAndQuestions();
        $current = $this->makeUser('Super Administrator', 'old-super-mail@example.com');
        $superRole = Role::query()->where('role_name', 'Super Administrator')->firstOrFail();

        $this->actingAs($current)->post('/users', [
            'username' => 'new.super.mail',
            'email' => 'new-super-mail@example.com',
            'password' => 'TempPass1!',
            'password_confirmation' => 'TempPass1!',
            'role_id' => $superRole->id,
        ])->assertRedirect(route('login'));

        Mail::assertSent(AccountCredentialsMail::class, function (AccountCredentialsMail $mail) {
            return $mail->hasTo('new-super-mail@example.com')
                && $mail->username === 'new.super.mail'
                && $mail->temporaryPassword === 'TempPass1!'
                && $mail->roleName === 'Super Administrator';
        });
    }

    public function test_new_super_admin_must_change_password_before_dashboard(): void
    {
        $this->seedRolesAndQuestions();
        $user = $this->makeUser('Super Administrator', 'fresh-super@example.com');
        $user->update(['must_change_password' => true, 'credentials_completed_at' => null]);

        $this->actingAs($user->fresh())->get('/')->assertRedirect(route('setup.password'));
    }

    public function test_setup_password_page_shows_strength_meter(): void
    {
        $this->seedRolesAndQuestions();
        $user = $this->makeUser('Super Administrator', 'setup-meter@example.com');
        $user->update(['must_change_password' => true, 'credentials_completed_at' => null]);

        $this->actingAs($user->fresh())
            ->get(route('setup.password'))
            ->assertOk()
            ->assertSee('Password strength')
            ->assertSee('Strength:');
    }

    public function test_setup_password_rejects_weak_password(): void
    {
        $this->seedRolesAndQuestions();
        $user = $this->makeUser('Super Administrator', 'setup-weak@example.com');
        $user->update(['must_change_password' => true, 'credentials_completed_at' => null]);

        $this->actingAs($user->fresh())
            ->from(route('setup.password'))
            ->post(route('setup.password'), [
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('setup.password'))
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_setup_password_accepts_medium_password(): void
    {
        $this->seedRolesAndQuestions();
        $user = $this->makeUser('Super Administrator', 'setup-ok@example.com');
        $user->update(['must_change_password' => true, 'credentials_completed_at' => null]);

        $this->actingAs($user->fresh())
            ->post(route('setup.password'), [
                'password' => 'Password1',
                'password_confirmation' => 'Password1',
            ])
            ->assertRedirect(route('setup.credentials'));

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_blocked_account_cannot_login(): void
    {
        $this->seedRolesAndQuestions();
        $user = $this->makeUser('User', 'blocked@example.com');
        $user->update(['status' => 'blocked', 'blocked_at' => now()]);

        $this->post('/login', [
            'username' => 'blocked',
            'password' => 'password',
        ])->assertSessionHasErrors('username');
    }

    public function test_registration_rejects_existing_employee_id(): void
    {
        $this->seedRolesAndQuestions();
        $existing = $this->makeUser('User', 'taken@example.com');

        $payload = $this->registrationPayload();
        $payload['employee_id'] = $existing->employee_id;

        $this->from(route('register'))
            ->post('/register', $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('employee_id');

        $this->assertStringContainsString(
            'already in use',
            session('errors')->first('employee_id')
        );
        $this->assertDatabaseMissing('users', ['email' => 'new.staff@example.com']);
    }

    public function test_registration_rejects_weak_password(): void
    {
        $this->seedRolesAndQuestions();

        $payload = $this->registrationPayload();
        $payload['password'] = 'password';
        $payload['password_confirmation'] = 'password';

        $this->from(route('register'))
            ->post('/register', $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');
    }

    public function test_registration_rejects_invalid_first_name_patterns(): void
    {
        $this->seedRolesAndQuestions();

        foreach (['jennel', 'JoHn', 'Jennel  P', 'Jennel2', 'Annna'] as $name) {
            $payload = $this->registrationPayload();
            $payload['first_name'] = $name;

            $this->from(route('register'))
                ->post('/register', $payload)
                ->assertRedirect(route('register'))
                ->assertSessionHasErrors('first_name');
        }
    }

    public function test_registration_rejects_letters_in_contact_number(): void
    {
        $this->seedRolesAndQuestions();
        $payload = $this->registrationPayload();
        $payload['contact_number'] = '0917ABC4567';

        $this->from(route('register'))
            ->post('/register', $payload)
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('contact_number');
    }

    public function test_registration_check_reports_taken_employee_id(): void
    {
        $this->seedRolesAndQuestions();
        $existing = $this->makeUser('User', 'lookup-taken@example.com');

        $this->get('/register/check?employee_id='.$existing->employee_id)
            ->assertOk()
            ->assertJsonPath('employee_id.available', false)
            ->assertJsonPath('employee_id.reason', 'taken');

        $this->get('/register/check?employee_id=EMP-9999')
            ->assertOk()
            ->assertJsonPath('employee_id.available', true);
    }

    public function test_registration_check_reports_pending_employee_id(): void
    {
        $this->seedRolesAndQuestions();

        RegistrationRequest::create([
            'employee_id' => 'EMP-5555',
            'first_name' => 'Pending',
            'last_name' => 'User',
            'email' => 'pending.user@example.com',
            'password_hash' => 'hash',
            'secret_answers' => [],
            'status' => 'pending',
        ]);

        $this->get('/register/check?employee_id=EMP-5555')
            ->assertOk()
            ->assertJsonPath('employee_id.available', false)
            ->assertJsonPath('employee_id.reason', 'pending');
    }

    public function test_mail_settings_do_not_override_array_mailer_during_tests(): void
    {
        config(['mail.default' => 'array']);
        app(\App\Services\MailSettings::class)->apply();
        $this->assertSame('array', config('mail.default'));
    }

    public function test_super_admin_can_delete_user_who_has_audit_logs(): void
    {
        $this->seedRolesAndQuestions();
        $super = $this->makeUser('Super Administrator', 'super@example.com');
        $staff = $this->makeUser('User', 'staff@example.com');

        \App\Models\AuditLog::create([
            'user_id' => $staff->id,
            'employee_id' => $staff->employee_id,
            'full_name' => $staff->fullName(),
            'role_name' => $staff->roleName(),
            'action' => 'login',
            'module' => 'auth',
            'details' => 'Signed in',
        ]);

        $this->actingAs($super)
            ->delete(route('users.destroy', $staff), [
                'confirm_email' => 'super@example.com',
                'confirm_password' => 'password',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'employee_id' => $staff->employee_id,
            'action' => 'login',
        ]);
    }

    public function test_last_super_admin_cannot_be_deleted(): void
    {
        $this->seedRolesAndQuestions();
        $super = $this->makeUser('Super Administrator', 'super@example.com');
        $admin = $this->makeUser('Administrator', 'admin@example.com');
        $otherSuper = $this->makeUser('Super Administrator', 'super2@example.com');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $otherSuper), [
                'reason' => 'Cleanup',
                'confirm_email' => 'admin@example.com',
                'confirm_password' => 'password',
            ])
            ->assertForbidden();

        $this->actingAs($super)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $otherSuper), [
                'confirm_email' => 'super@example.com',
                'confirm_password' => 'password',
            ])
            ->assertRedirect(route('users.confirm-delete', $otherSuper))
            ->assertSessionHasErrors('confirm_employee_id');
        $this->assertDatabaseHas('users', ['id' => $otherSuper->id]);

        $this->actingAs($super)
            ->get(route('users.confirm-delete', $otherSuper))
            ->assertOk()
            ->assertSee('Confirm account deletion')
            ->assertSee($otherSuper->employee_id)
            ->assertSee('Your email')
            ->assertSee('Your password');

        $this->actingAs($super)
            ->from(route('users.confirm-delete', $otherSuper))
            ->delete(route('users.destroy', $otherSuper), [
                'confirm_employee_id' => 'WRONG-ID',
                'confirm_email' => 'super@example.com',
                'confirm_password' => 'password',
            ])
            ->assertRedirect(route('users.confirm-delete', $otherSuper))
            ->assertSessionHasErrors('confirm_employee_id');
        $this->assertDatabaseHas('users', ['id' => $otherSuper->id]);

        $this->actingAs($super)
            ->from(route('users.confirm-delete', $otherSuper))
            ->delete(route('users.destroy', $otherSuper), [
                'confirm_employee_id' => $otherSuper->employee_id,
                'confirm_email' => 'wrong@example.com',
                'confirm_password' => 'password',
            ])
            ->assertRedirect(route('users.confirm-delete', $otherSuper))
            ->assertSessionHasErrors('confirm_email');
        $this->assertDatabaseHas('users', ['id' => $otherSuper->id]);

        $this->actingAs($super)
            ->delete(route('users.destroy', $otherSuper), [
                'confirm_employee_id' => $otherSuper->employee_id,
                'confirm_email' => 'super@example.com',
                'confirm_password' => 'password',
            ])
            ->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $otherSuper->id]);

        $this->actingAs($super)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $super), [
                'confirm_email' => 'super@example.com',
                'confirm_password' => 'password',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', 'You cannot delete your own account.');
        $this->assertDatabaseHas('users', ['id' => $super->id]);
    }

    public function test_admin_delete_request_requires_email_and_password(): void
    {
        $this->seedRolesAndQuestions();
        $manage = \App\Models\Permission::create([
            'key' => 'users.manage',
            'name' => 'Manage users',
            'module' => 'users',
        ]);
        $adminRole = Role::query()->where('role_name', 'Administrator')->firstOrFail();
        $adminRole->permissions()->sync([$manage->id]);

        $admin = $this->makeUser('Administrator', 'admin@example.com');
        $target = $this->makeUser('User', 'target-del@example.com');

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $target), [
                'reason' => 'Inactive account',
                'confirm_email' => 'admin@example.com',
                'confirm_password' => 'wrong-password',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('confirm_password')
            ->assertSessionHas('open_delete_id', $target->id);

        $this->assertDatabaseMissing('delete_requests', ['target_user_id' => $target->id]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target), [
                'reason' => 'Inactive account',
                'confirm_email' => 'admin@example.com',
                'confirm_password' => 'password',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('delete_requests', [
            'target_user_id' => $target->id,
            'requested_by' => $admin->id,
            'status' => 'pending',
        ]);
    }

    public function test_administrator_cannot_open_privileges(): void
    {
        $this->seedRolesAndQuestions();
        $admin = $this->makeUser('Administrator', 'admin@example.com');
        $target = $this->makeUser('User', 'target@example.com');

        $this->actingAs($admin)->get(route('users.privileges', $target))->assertForbidden();
    }

    private function seedRolesAndQuestions(): void
    {
        Role::create(['role_name' => 'Super Administrator', 'description' => 'Super']);
        Role::create(['role_name' => 'Administrator', 'description' => 'Admin']);
        Role::create(['role_name' => 'User', 'description' => 'User']);
        foreach (config('secret_questions') as $question) {
            SecretQuestion::query()->firstOrCreate(
                ['question' => $question],
                ['is_active' => true]
            );
        }
    }

    private function makeUser(string $roleName, string $email): User
    {
        $role = Role::query()->where('role_name', $roleName)->firstOrFail();

        return User::factory()->create([
            'email' => $email,
            'username' => \App\Support\Username::fromEmail($email),
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
            'first_name' => 'Test',
            'last_name' => $roleName,
        ]);
    }

    private function registrationPayload(): array
    {
        $ids = SecretQuestion::query()->orderBy('id')->pluck('id')->all();

        return [
            'employee_id' => 'EMP-7777',
            'first_name' => 'Nina',
            'middle_name' => 'P',
            'last_name' => 'Cruz',
            'extension_name' => 'Jr.',
            'birthdate' => '1995-06-15',
            'sex' => 'Female',
            'purok_street' => 'Purok 2',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Cabadbaran',
            'province' => 'Agusan Del Norte',
            'zipcode' => '8605',
            'contact_number' => '09171234567',
            'email' => 'new.staff@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'question_1' => $ids[0],
            'question_2' => $ids[1],
            'question_3' => $ids[2],
            'answer_1' => 'one',
            'answer_2' => 'two',
            'answer_3' => 'three',
        ];
    }
}
