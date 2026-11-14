<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_theme_preferences(): void
    {
        Role::create(['role_name' => 'User', 'description' => 'User']);
        $role = Role::query()->where('role_name', 'User')->firstOrFail();
        $user = User::factory()->create([
            'email' => 'theme@example.com',
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
            'first_name' => 'Theme',
            'last_name' => 'User',
            'name' => 'USER, Theme',
        ]);

        $this->actingAs($user)
            ->post(route('profile.update'), [
                'name' => 'Theme User',
                'theme_mode' => 'dark',
                'theme_color' => '#1d4ed8',
                'theme_background' => 'theme1',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('dark', $user->theme_mode);
        $this->assertSame('#1d4ed8', $user->theme_color);
        $this->assertSame('theme1', $user->theme_background);
        $this->assertArrayHasKey('#ffffff', User::THEME_COLORS);
        $whiteTheme = new User(['theme_color' => '#ffffff']);
        $this->assertTrue($whiteTheme->themeSidebarIsLight());
        $this->assertSame('#ffffff', $whiteTheme->themeSidebarColor());
        $this->assertSame('#0f172a', $whiteTheme->themeOnPrimaryColor());
        $this->assertTrue($user->themeBackgroundIsImage());
        $this->assertStringContainsString('images/themes/theme1.png', (string) $user->themeBackgroundImageUrl());
        $this->assertSame('Theme User', $user->name);
        $this->assertSame('Theme User', $user->displayName());
        $this->assertSame('USER, Theme', $user->fullName());

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Theme User')
            ->assertSee('Appearance')
            ->assertSee('Campus theme 1')
            ->assertSee('images/themes/theme1.png');
    }

    public function test_uploaded_profile_photo_is_served_from_the_account(): void
    {
        Storage::fake('public');
        Role::create(['role_name' => 'User', 'description' => 'User']);
        $role = Role::query()->where('role_name', 'User')->firstOrFail();
        $user = User::factory()->create([
            'email' => 'photo@example.com',
            'role_id' => $role->id,
            'status' => 'active',
            'must_change_password' => false,
            'credentials_completed_at' => now(),
            'first_name' => 'Photo',
            'last_name' => 'User',
            'name' => 'USER, Photo',
        ]);

        $this->actingAs($user)
            ->post(route('profile.update'), [
                'name' => 'Photo User',
                'theme_mode' => 'light',
                'theme_color' => '#ffffff',
                'theme_background' => 'sand',
                'avatar' => UploadedFile::fake()->createWithContent(
                    'avatar.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='),
                    'image/png'
                ),
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->has_avatar);
        $this->assertNotNull($user->avatarUrl());

        $this->actingAs($user)
            ->get($user->avatarUrl())
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }
}
