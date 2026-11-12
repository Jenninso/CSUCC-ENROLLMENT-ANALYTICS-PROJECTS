<?php

namespace App\Http\Controllers;

use App\Models\DeleteRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AuditLogger;
use App\Support\PasswordPolicy;
use App\Support\Username;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('role')
            ->withCount(['loginLogs as open_login_logs_count' => fn ($q) => $q->whereNull('time_out')])
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', 'like', '%'.strtoupper($v).'%'))
            ->when($request->role_id, fn ($q, $v) => $q->where('role_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->account_category, fn ($q, $v) => $q->whereAccountCategory($v))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::query()->where('role_name', '!=', 'Student')->orderBy('role_name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function create(AccountService $accounts)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $roles = Role::query()
            ->whereIn('role_name', ['Super Administrator', 'Administrator', 'User'])
            ->orderBy('role_name')
            ->get();

        return view('users.create', [
            'roles' => $roles,
            'nextEmployeeId' => $accounts->nextEmployeeId(),
        ]);
    }

    public function store(Request $request, AccountService $accounts, AuditLogger $audit)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $allowedRoles = Role::query()
            ->whereIn('role_name', ['Super Administrator', 'Administrator', 'User'])
            ->pluck('id');

        $request->merge([
            'username' => Username::normalize($request->input('username')),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'username' => Username::rules(),
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', Rule::in($allowedRoles->all())],
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
        ], [
            'username.regex' => Username::message(),
        ]);

        $role = Role::query()->findOrFail($validated['role_id']);
        $employeeId = $accounts->nextEmployeeId();
        $temporaryPassword = $validated['password'];

        $user = $accounts->createUser([
            'employee_id' => $employeeId,
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $temporaryPassword,
            'role_id' => $validated['role_id'],
            'status' => 'active',
            'must_change_password' => true,
            'credentials_completed_at' => null,
        ]);

        $emailed = $accounts->notifyRoleAssigned($user, $temporaryPassword, $role->role_name);

        $message = 'Created '.$role->role_name.' account '.$user->employee_id.' (@'.$user->username.') with a temporary password.';
        $message .= $emailed
            ? ' Username and temporary password were sent to '.$user->email.'.'
            : ' Warning: the credentials email could not be sent. Check SMTP settings and share the temporary password securely.';

        if ($role->role_name === 'Super Administrator') {
            $deactivated = $accounts->deactivatePreviousSuperAdmins($user);
            if ($deactivated > 0) {
                $audit->log('deactivate', 'users', 'Deactivated the previous Super Administrator account after creating '.$user->employee_id.'.');
            }
            $message .= ' Previous Super Administrator accounts are now Deactivated.';
        }

        $audit->log('create', 'users', $message);

        if ($role->role_name === 'Super Administrator' && auth()->id() !== $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', $message.' Sign in to the new Super Administrator account to change the temporary password and complete credentials.');
        }

        return redirect()->route('users.index')->with('success', $message.' The new account must change password and complete credentials on first login.');
    }

    public function edit(User $user)
    {
        $this->assertCanUpdate($user);
        $roles = Role::orderBy('role_name')->get()
            ->reject(fn ($role) => $role->role_name === 'Student')
            ->values();
        if (! auth()->user()->isSuperAdmin()) {
            $roles = $roles->whereIn('role_name', ['Administrator', 'User'])->values();
        }

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user, AccountService $accounts, AuditLogger $audit)
    {
        $this->assertCanUpdate($user);

        $roleRule = ['required', 'exists:roles,id'];
        if (! auth()->user()->isSuperAdmin()) {
            $ids = Role::query()->whereIn('role_name', ['Administrator', 'User'])->pluck('id')->all();
            $roleRule = ['required', Rule::in($ids)];
        }
        $studentRoleId = Role::query()->where('role_name', 'Student')->value('id');
        if ($studentRoleId) {
            $roleRule[] = Rule::notIn([(int) $studentRoleId]);
        }

        $request->merge([
            'employee_id' => strtoupper(trim((string) $request->input('employee_id'))),
            'username' => Username::normalize($request->input('username')),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:50', 'unique:users,employee_id,'.$user->id],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => Username::rules((string) $user->id),
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role_id' => $roleRule,
            'password' => [
                'nullable',
                'confirmed',
                PasswordPolicy::rule(),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if (! is_string($value) || ! PasswordPolicy::isAcceptable($value)) {
                        $fail(PasswordPolicy::message());
                    }
                },
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'blocked'])],
        ], [
            'username.regex' => Username::message(),
        ]);

        $previousRoleId = (int) $user->role_id;
        $validated['name'] = User::composeName($validated['first_name'], $validated['middle_name'] ?? null, $validated['last_name']);
        $temporaryPassword = $validated['password'] ?? null;
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['must_change_password'] = true;
        }
        if (! auth()->user()->isSuperAdmin()) {
            unset($validated['status']);
        }

        $user->update($validated);
        $audit->log('update', 'users', 'Updated account information of '.$user->accountLabel());

        $roleChanged = $previousRoleId !== (int) $user->role_id;
        $message = 'Account updated.';

        if ($roleChanged || $temporaryPassword) {
            $user->load('role');
            $roleName = $user->roleName() ?: 'User';

            if ($temporaryPassword) {
                $emailed = $accounts->notifyRoleAssigned($user, $temporaryPassword, $roleName);
                $message = $roleChanged
                    ? 'Account role updated to '.$roleName.'.'
                    : 'Temporary password updated.';
                $message .= $emailed
                    ? ' Username and temporary password were sent to '.$user->email.'.'
                    : ' Warning: the credentials email could not be sent. Check SMTP settings.';
            } elseif ($roleChanged) {
                $message = 'Account role updated to '.$roleName.'.';
            }
        }

        return redirect()->route('users.index')->with('success', $message);
    }

    public function confirmDestroy(User $user, AccountService $accounts)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($accounts->isLastActiveSuperAdmin($user)) {
            return redirect()->route('users.index')->with('error', 'You cannot delete the last Super Administrator account.');
        }

        return view('users.confirm-delete', compact('user'));
    }

    public function destroy(Request $request, User $user, AccountService $accounts, AuditLogger $audit)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $credentials = $accounts->confirmActorCredentials(
            auth()->user(),
            $request->input('confirm_email'),
            $request->input('confirm_password')
        );

        if (auth()->user()->isSuperAdmin()) {
            if ($accounts->isLastActiveSuperAdmin($user)) {
                return back()->with('error', 'You cannot delete the last Super Administrator account.');
            }

            if ($credentials !== true) {
                return redirect()
                    ->route('users.confirm-delete', $user)
                    ->withInput($request->except('confirm_password'))
                    ->withErrors($credentials);
            }

            if ($user->isSuperAdmin()) {
                $confirmed = $accounts->confirmSuperAdminDeletion($request->input('confirm_employee_id'), $user);
                if ($confirmed !== true) {
                    return redirect()
                        ->route('users.confirm-delete', $user)
                        ->withInput($request->except('confirm_password'))
                        ->withErrors(['confirm_employee_id' => $confirmed]);
                }
            }

            $audit->log('delete', 'users', 'Deleted '.$user->accountCategoryLabel().' '.$user->accountLabel());
            $accounts->deleteUser($user);

            return redirect()->route('users.index')->with('success', 'Account deleted.');
        }

        abort_unless(auth()->user()->isAdministrator(), 403);
        $this->assertCanUpdate($user);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'confirm_email' => ['required', 'email'],
            'confirm_password' => ['required', 'string'],
        ]);

        if ($credentials !== true) {
            return redirect()
                ->route('users.index')
                ->withInput($request->except('confirm_password'))
                ->withErrors($credentials)
                ->with('open_delete_id', $user->id);
        }

        DeleteRequest::create([
            'target_user_id' => $user->id,
            'requested_by' => auth()->id(),
            'reason' => $validated['reason'],
            'user_snapshot' => $accounts->snapshot($user),
            'status' => 'pending',
        ]);

        $audit->log('delete_request', 'users', 'Requested deletion of '.$user->accountCategoryLabel().' '.$user->accountLabel());

        return redirect()->route('users.index')->with('success', 'Delete request sent to the Super Administrator.');
    }

    public function block(Request $request, User $user, AuditLogger $audit)
    {
        $this->assertCanUpdate($user);
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $user->update([
            'status' => 'blocked',
            'blocked_at' => now(),
            'blocked_reason' => $validated['reason'] ?? 'Blocked by '.auth()->user()->fullName(),
        ]);
        $audit->log('block', 'users', 'Blocked user '.$user->accountLabel());

        return back()->with('success', 'Account blocked.');
    }

    public function unblock(User $user, AuditLogger $audit)
    {
        $this->assertCanUpdate($user);
        $user->update([
            'status' => 'active',
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);
        $audit->log('unblock', 'users', 'Unblocked user '.$user->accountLabel());

        return back()->with('success', 'Account unblocked.');
    }

    public function activate(User $user, AccountService $accounts, AuditLogger $audit)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->assertCanUpdate($user);
        abort_if($user->id === auth()->id(), 403, 'You cannot activate your own account from here.');

        $result = $accounts->activateUser($user);
        if ($result !== true) {
            return back()->withErrors(['status' => $result]);
        }

        $audit->log('activate', 'users', 'Activated account '.$user->accountLabel());

        $message = 'Account activated.';
        if ($user->fresh()->isSuperAdmin()) {
            $message .= ' Other Super Administrator accounts were set to Deactivated.';
        }

        return back()->with('success', $message);
    }

    public function deactivate(User $user, AccountService $accounts, AuditLogger $audit)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->assertCanUpdate($user);
        abort_if($user->id === auth()->id(), 403, 'You cannot deactivate your own account.');

        $result = $accounts->deactivateUser($user);
        if ($result !== true) {
            return back()->withErrors(['status' => $result]);
        }

        $audit->log('deactivate', 'users', 'Deactivated account '.$user->accountLabel());

        return back()->with('success', 'Account deactivated. Sign-in and Forgot Password are blocked until a Super Administrator activates it.');
    }

    public function privileges(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        $assigned = $user->permissions()->pluck('permissions.id')->all();
        if (! $assigned) {
            $assigned = $user->role?->permissions()->pluck('permissions.id')->all() ?? [];
        }

        return view('users.privileges', compact('user', 'permissions', 'assigned'));
    }

    public function updatePrivileges(Request $request, User $user, AuditLogger $audit)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $ids = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ])['permissions'] ?? [];

        $user->permissions()->sync($ids);
        $audit->log('privileges', 'users', 'Updated privileges for '.$user->accountLabel());

        return redirect()->route('users.index')->with('success', 'Privileges updated.');
    }

    private function assertCanUpdate(User $user): void
    {
        $actor = auth()->user();
        abort_unless($actor->isStaffAdmin(), 403);
        if ($user->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            abort(403, 'Administrators cannot modify Super Administrator accounts.');
        }
    }
}
