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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
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
    'has_avatar',
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
    public const DEFAULT_THEME_COLOR = '#ffffff';

    public const THEME_COLORS = [
        '#0b5d3b' => 'Forest green',
        '#047857' => 'Emerald',
        '#0f766e' => 'Teal',
        '#0e7490' => 'Cyan',
        '#1d4ed8' => 'Royal blue',
        '#4338ca' => 'Indigo',
        '#b45309' => 'Amber',
        '#be123c' => 'Rose',
        '#334155' => 'Slate',
        '#ffffff' => 'White',
    ];

    /** Soft two-tone previews for the theme color picker (primary hex stays solid in CSS). */
    public const THEME_COLOR_SWATCHES = [
        '#0b5d3b' => 'linear-gradient(145deg, #34d399 0%, #0b5d3b 72%)',
        '#047857' => 'linear-gradient(145deg, #6ee7b7 0%, #047857 72%)',
        '#0f766e' => 'linear-gradient(145deg, #5eead4 0%, #0f766e 72%)',
        '#0e7490' => 'linear-gradient(145deg, #67e8f9 0%, #0e7490 72%)',
        '#1d4ed8' => 'linear-gradient(145deg, #93c5fd 0%, #1d4ed8 72%)',
        '#4338ca' => 'linear-gradient(145deg, #a5b4fc 0%, #4338ca 72%)',
        '#b45309' => 'linear-gradient(145deg, #fcd34d 0%, #b45309 72%)',
        '#be123c' => 'linear-gradient(145deg, #fb7185 0%, #be123c 72%)',
        '#334155' => 'linear-gradient(145deg, #94a3b8 0%, #334155 72%)',
        '#ffffff' => 'linear-gradient(145deg, #ffffff 0%, #e2e8f0 100%)',
    ];

    public const THEME_BACKGROUNDS = [
        'default' => ['label' => 'Soft gray', 'type' => 'color', 'light' => '#f3f4f6', 'dark' => '#0f172a'],
        'mint' => ['label' => 'Mint wash', 'type' => 'color', 'light' => '#ecfdf5', 'dark' => '#042f2e'],
        'sky' => ['label' => 'Sky wash', 'type' => 'color', 'light' => '#eff6ff', 'dark' => '#0c1a2e'],
        'sand' => ['label' => 'Warm sand', 'type' => 'color', 'light' => '#faf6f1', 'dark' => '#1c1917'],
        'slate' => ['label' => 'Cool slate', 'type' => 'color', 'light' => '#f1f5f9', 'dark' => '#020617'],
        'aurora' => [
            'label' => 'Emerald aurora',
            'type' => 'gradient',
            'light' => '#ecfdf5',
            'dark' => '#042f2e',
            'light_css' => 'radial-gradient(1200px 520px at 12% -8%, rgba(16,185,129,0.28), transparent 58%), radial-gradient(900px 480px at 88% 8%, rgba(45,212,191,0.22), transparent 55%), linear-gradient(160deg, #f0fdf4 0%, #ecfeff 48%, #f8fafc 100%)',
            'dark_css' => 'radial-gradient(1000px 480px at 10% -10%, rgba(16,185,129,0.22), transparent 55%), radial-gradient(820px 420px at 90% 0%, rgba(45,212,191,0.16), transparent 50%), linear-gradient(165deg, #022c22 0%, #0f172a 55%, #042f2e 100%)',
        ],
        'lagoon' => [
            'label' => 'Teal lagoon',
            'type' => 'gradient',
            'light' => '#f0fdfa',
            'dark' => '#042f2e',
            'light_css' => 'radial-gradient(1100px 500px at 0% 0%, rgba(20,184,166,0.26), transparent 56%), radial-gradient(860px 460px at 100% 20%, rgba(14,165,233,0.18), transparent 52%), linear-gradient(155deg, #f0fdfa 0%, #ecfeff 45%, #f8fafc 100%)',
            'dark_css' => 'radial-gradient(980px 460px at 5% -8%, rgba(20,184,166,0.2), transparent 54%), radial-gradient(780px 400px at 95% 5%, rgba(14,165,233,0.14), transparent 50%), linear-gradient(165deg, #042f2e 0%, #0c4a6e 50%, #0f172a 100%)',
        ],
        'horizon' => [
            'label' => 'Sky horizon',
            'type' => 'gradient',
            'light' => '#eff6ff',
            'dark' => '#0c1a2e',
            'light_css' => 'radial-gradient(1000px 480px at 15% -5%, rgba(59,130,246,0.22), transparent 55%), radial-gradient(880px 440px at 85% 10%, rgba(56,189,248,0.2), transparent 52%), linear-gradient(160deg, #eff6ff 0%, #f0f9ff 50%, #f8fafc 100%)',
            'dark_css' => 'radial-gradient(960px 440px at 12% -8%, rgba(59,130,246,0.2), transparent 55%), radial-gradient(760px 400px at 90% 0%, rgba(14,165,233,0.14), transparent 50%), linear-gradient(165deg, #0c1a2e 0%, #0f172a 55%, #082f49 100%)',
        ],
        'dawn' => [
            'label' => 'Campus dawn',
            'type' => 'gradient',
            'light' => '#fffbeb',
            'dark' => '#1c1917',
            'light_css' => 'radial-gradient(1100px 500px at 8% -10%, rgba(251,191,36,0.22), transparent 56%), radial-gradient(900px 460px at 92% 5%, rgba(16,185,129,0.16), transparent 52%), linear-gradient(155deg, #fffbeb 0%, #f0fdf4 48%, #f8fafc 100%)',
            'dark_css' => 'radial-gradient(980px 460px at 8% -8%, rgba(245,158,11,0.16), transparent 54%), radial-gradient(780px 400px at 92% 0%, rgba(16,185,129,0.12), transparent 50%), linear-gradient(165deg, #1c1917 0%, #14532d 48%, #0f172a 100%)',
        ],
        'mist' => [
            'label' => 'Forest mist',
            'type' => 'gradient',
            'light' => '#f3faf6',
            'dark' => '#0a2f22',
            'light_css' => 'radial-gradient(1050px 480px at 20% -8%, rgba(11,93,59,0.18), transparent 58%), radial-gradient(820px 420px at 80% 15%, rgba(52,211,153,0.16), transparent 52%), linear-gradient(150deg, #f3faf6 0%, #ecfdf5 40%, #f8fafc 100%)',
            'dark_css' => 'radial-gradient(960px 440px at 15% -10%, rgba(16,185,129,0.18), transparent 55%), radial-gradient(740px 380px at 88% 5%, rgba(5,150,105,0.12), transparent 50%), linear-gradient(165deg, #0a2f22 0%, #052e16 50%, #0f172a 100%)',
        ],
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

    /**
     * New accounts open on the light sidebar with green labels.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'theme_mode' => 'light',
        'theme_color' => self::DEFAULT_THEME_COLOR,
        'theme_background' => 'sand',
        'has_avatar' => false,
    ];

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
            'has_avatar' => 'boolean',
            'credentials_completed_at' => 'datetime',
            'birthdate' => 'date',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function avatar(): HasOne
    {
        return $this->hasOne(UserAvatar::class);
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

    public function displayName(): string
    {
        $display = trim((string) $this->name);

        return $display !== '' ? $display : $this->fullName();
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

    public function isStudent(): bool
    {
        return $this->hasRole('Student');
    }

    public function studentNumber(): string
    {
        return strtoupper(trim((string) $this->employee_id));
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
        if ($this->has_avatar) {
            return route('profile.avatar', $this, false);
        }

        if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
            return route('profile.avatar', $this, false);
        }

        return null;
    }

    public function initials(): string
    {
        $name = trim($this->displayName());

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
        $color = strtolower((string) ($this->theme_color ?: self::DEFAULT_THEME_COLOR));

        return array_key_exists($color, self::THEME_COLORS) ? $color : self::DEFAULT_THEME_COLOR;
    }

    public function resolvedThemeBackgroundKey(): string
    {
        $key = (string) ($this->theme_background ?: 'sand');

        return array_key_exists($key, self::THEME_BACKGROUNDS) ? $key : 'sand';
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

    public function themeBackgroundIsGradient(): bool
    {
        $preset = self::THEME_BACKGROUNDS[$this->resolvedThemeBackgroundKey()];

        return ($preset['type'] ?? 'color') === 'gradient';
    }

    public function themeBackgroundImageUrl(): ?string
    {
        $preset = self::THEME_BACKGROUNDS[$this->resolvedThemeBackgroundKey()];
        if (($preset['type'] ?? 'color') !== 'image' || empty($preset['image'])) {
            return null;
        }

        return asset($preset['image']);
    }

    public function themeBackgroundGradientCss(bool $dark = false): ?string
    {
        $preset = self::THEME_BACKGROUNDS[$this->resolvedThemeBackgroundKey()];
        if (($preset['type'] ?? 'color') !== 'gradient') {
            return null;
        }

        $key = $dark ? 'dark_css' : 'light_css';

        return $preset[$key] ?? null;
    }

    public function themeColorSwatchCss(): string
    {
        $color = $this->resolvedThemeColor();

        return self::THEME_COLOR_SWATCHES[$color] ?? $color;
    }

    public function themeSidebarColor(): string
    {
        $color = $this->resolvedThemeColor();
        $map = [
            '#0b5d3b' => '#0a2f22',
            '#047857' => '#064e3b',
            '#0f766e' => '#134e4a',
            '#0e7490' => '#164e63',
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
