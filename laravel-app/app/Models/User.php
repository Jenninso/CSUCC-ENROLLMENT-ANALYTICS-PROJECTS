<?php

namespace App\Models;

use App\Support\AccountCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Database\Factories\UserFactory;

#[Fillable([
    'employee_id',
    'name',
    'first_name',
    'middle_name',
    'last_name',
    'extension_name',
    'birthdate',
    'sex',
    'address',
    'purok_street',
    'barangay',
    'city_municipality',
    'province',
    'zipcode',
    'contact_number',
    'email',
    'username',
    'avatar_path',
    'theme_mode',
    'theme_color',
    'theme_background',
    'password',
    'role_id',
    'status',
    'blocked_at',
    'blocked_reason',
    'must_change_password',
    'credentials_completed_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const THEME_COLORS = [
        '#0b5d3b' => 'Forest green',
        '#047857' => 'Emerald',
        '#0f766e' => 'Teal',
        '#1d4ed8' => 'Royal blue',
        '#4338ca' => 'Indigo',
        '#b45309' => 'Amber',
        '#be123c' => 'Rose',
        '#334155' => 'Slate',
        '#ffffff' => 'White',
    ];

    public const THEME_BACKGROUNDS = [
        'default' => ['label' => 'Soft gray', 'type' => 'color', 'light' => '#f3f4f6', 'dark' => '#0f172a'],
        'mint' => ['label' => 'Mint wash', 'type' => 'color', 'light' => '#ecfdf5', 'dark' => '#042f2e'],
        'sky' => ['label' => 'Sky wash', 'type' => 'color', 'light' => '#eff6ff', 'dark' => '#0c1a2e'],
        'sand' => ['label' => 'Warm sand', 'type' => 'color', 'light' => '#faf6f1', 'dark' => '#1c1917'],
        'slate' => ['label' => 'Cool slate', 'type' => 'color', 'light' => '#f1f5f9', 'dark' => '#020617'],
        'theme1' => [
            'label' => 'Campus theme 1',
            'type' => 'image',
            'image' => 'images/themes/theme1.png',
            'light' => '#e8f0ec',
            'dark' => '#0a2f22',
        ],
        'theme2' => [
            'label' => 'Campus theme 2',
            'type' => 'image',
            'image' => 'images/themes/theme2.jpg',
            'light' => '#eef2f7',
            'dark' => '#0f172a',
        ],
        'theme3' => [
            'label' => 'Campus theme 3',
            'type' => 'image',
            'image' => 'images/themes/theme3.png',
            'light' => '#f3efe8',
            'dark' => '#1c1917',
        ],
        'theme4' => [
            'label' => 'Campus theme 4',
            'type' => 'image',
            'image' => 'images/themes/theme4.png',
            'light' => '#edf5f1',
            'dark' => '#042f2e',
        ],
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    private ?array $permissionCache = null;

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            $user->auditLogs()->update(['user_id' => null]);
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'blocked_at' => 'datetime',
            'must_change_password' => 'boolean',
            'credentials_completed_at' => 'datetime',
            'birthdate' => 'date',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function secretAnswers(): HasMany
    {
        return $this->hasMany(UserSecretAnswer::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission');
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function roleName(): string
    {
        return (string) ($this->role?->role_name ?? '');
    }

    public function fullName(): string
    {
        $last = trim((string) $this->last_name);
        $first = trim((string) $this->first_name);
        $middle = trim((string) $this->middle_name);
        $extension = trim((string) $this->extension_name);

        if ($last !== '' || $first !== '') {
            $given = trim($first.($middle !== '' ? ' '.$middle : '').($extension !== '' ? ' '.$extension : ''));

            return $last !== '' ? strtoupper($last).($given !== '' ? ', '.$given : '') : $given;
        }

        return (string) $this->name;
    }

    public function hasRole(string ...$roles): bool
    {
        $name = $this->roleName();
        foreach ($roles as $role) {
            if (strcasecmp($name, $role) === 0) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Administrator');
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('Administrator');
    }

    public function isStaffAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->isAdministrator();
    }

    public function isUserRole(): bool
    {
        return $this->hasRole('User');
    }

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'inactive' => 'Deactivated',
            'blocked' => 'Blocked',
            default => ucfirst((string) ($this->status ?: 'unknown')),
        };
    }

    public function isOnline(): bool
    {
        if (array_key_exists('open_login_logs_count', $this->attributes)) {
            return (int) $this->attributes['open_login_logs_count'] > 0;
        }

        if ($this->relationLoaded('loginLogs')) {
            return $this->loginLogs->contains(fn (LoginLog $log) => $log->time_out === null);
        }

        return $this->loginLogs()->whereNull('time_out')->exists();
    }

    public function presenceLabel(): string
    {
        return $this->isOnline() ? 'Online' : 'Offline';
    }

    public function mustChangePassword(): bool
    {
        return (bool) $this->must_change_password;
    }

    public function hasCompletedCredentials(): bool
    {
        return $this->credentials_completed_at !== null;
    }

    /**
     * OTP is required only on first-time setup logins (temporary password / incomplete credentials).
     * Returning users with a confirmed password sign in with email + password only.
     */
    public function requiresLoginOtp(): bool
    {
        return $this->mustChangePassword() || ! $this->hasCompletedCredentials();
    }

    public function accountLabel(): string
    {
        $name = $this->fullName();

        return trim($this->employee_id.($name !== '' ? ' ('.$name.')' : ''));
    }

    public function accountCategory(): string
    {
        return AccountCategory::fromEmail($this->email);
    }

    public function accountCategoryLabel(): string
    {
        return AccountCategory::label($this->accountCategory());
    }

    public function isInstitutionalAccount(): bool
    {
        return $this->accountCategory() === AccountCategory::INSTITUTIONAL;
    }

    public function isPersonalAccount(): bool
    {
        return $this->accountCategory() === AccountCategory::PERSONAL;
    }

    public function scopeWhereAccountCategory(Builder $query, ?string $category): Builder
    {
        $category = strtolower(trim((string) $category));
        if (! in_array($category, [AccountCategory::INSTITUTIONAL, AccountCategory::PERSONAL], true)) {
            return $query;
        }

        $domains = array_values(array_filter(array_map(
            fn ($domain) => strtolower(trim((string) $domain)),
            config('accounts.institutional_email_domains', [])
        )));

        if ($domains === []) {
            return $category === AccountCategory::PERSONAL ? $query : $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $outer) use ($category, $domains) {
            foreach ($domains as $domain) {
                $exact = '%@'.$domain;
                $sub = '%.'.$domain;
                if ($category === AccountCategory::INSTITUTIONAL) {
                    $outer->orWhereRaw('LOWER(email) LIKE ?', [$exact])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$sub]);
                } else {
                    $outer->whereRaw('LOWER(email) NOT LIKE ?', [$exact])
                        ->whereRaw('LOWER(email) NOT LIKE ?', [$sub]);
                }
            }
        });
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return asset('storage/'.$this->avatar_path);
    }

    public function initials(): string
    {
        $name = trim($this->fullName() ?: (string) $this->name);

        return $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : 'U';
    }

    public function resolvedThemeMode(): string
    {
        $mode = $this->theme_mode ?: 'light';

        if ($mode === 'system') {
            return 'light';
        }

        return in_array($mode, ['light', 'dark'], true) ? $mode : 'light';
    }

    public function resolvedThemeColor(): string
    {
        $color = strtolower((string) ($this->theme_color ?: '#0b5d3b'));

        return array_key_exists($color, self::THEME_COLORS) ? $color : '#0b5d3b';
    }

    public function resolvedThemeBackgroundKey(): string
    {
        $key = (string) ($this->theme_background ?: 'default');

        return array_key_exists($key, self::THEME_BACKGROUNDS) ? $key : 'default';
    }

    public function resolvedThemeBackground(): string
    {
        $preset = self::THEME_BACKGROUNDS[$this->resolvedThemeBackgroundKey()];

        return $this->resolvedThemeMode() === 'dark' ? $preset['dark'] : $preset['light'];
    }

    public function themeBackgroundIsImage(): bool
    {
        $preset = self::THEME_BACKGROUNDS[$this->resolvedThemeBackgroundKey()];

        return ($preset['type'] ?? 'color') === 'image';
    }

    public function themeBackgroundImageUrl(): ?string
    {
        $preset = self::THEME_BACKGROUNDS[$this->resolvedThemeBackgroundKey()];
        if (($preset['type'] ?? 'color') !== 'image' || empty($preset['image'])) {
            return null;
        }

        return asset($preset['image']);
    }

    public function themeSidebarColor(): string
    {
        $color = $this->resolvedThemeColor();
        $map = [
            '#0b5d3b' => '#0a2f22',
            '#047857' => '#064e3b',
            '#0f766e' => '#134e4a',
            '#1d4ed8' => '#1e3a8a',
            '#4338ca' => '#312e81',
            '#b45309' => '#78350f',
            '#be123c' => '#881337',
            '#334155' => '#0f172a',
            '#ffffff' => '#ffffff',
        ];

        return $map[$color] ?? '#0a2f22';
    }

    public function themeSidebarIsLight(): bool
    {
        return $this->resolvedThemeColor() === '#ffffff';
    }

    /** Text/icon color on top of the primary theme color. */
    public function themeOnPrimaryColor(): string
    {
        return $this->themeSidebarIsLight() ? '#0f172a' : '#ffffff';
    }

    public function canManageUsers(): bool
    {
        return $this->isStaffAdmin();
    }

    public function canAccess(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->permissionCache === null) {
            if ($this->relationLoaded('permissions') ? $this->permissions->isNotEmpty() : $this->permissions()->exists()) {
                $this->permissionCache = $this->permissions()->pluck('key')->all();
            } else {
                $this->permissionCache = $this->role?->permissions()->pluck('key')->all() ?? [];
            }
        }

        return in_array($key, $this->permissionCache, true);
    }

    public static function composeName(?string $first, ?string $middle, ?string $last, ?string $extension = null): string
    {
        $last = trim((string) $last);
        $first = trim((string) $first);
        $middle = trim((string) $middle);
        $extension = trim((string) $extension);
        $given = trim($first.($middle !== '' ? ' '.$middle : '').($extension !== '' ? ' '.$extension : ''));

        if ($last !== '' && $given !== '') {
            return strtoupper($last).', '.$given;
        }

        return $given !== '' ? $given : $last;
    }
}
