@extends('layouts.app')
@section('title', 'Profile & Appearance')

@section('content')
@php
    $user = $user ?? auth()->user();
@endphp

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
      class="max-w-3xl space-y-6"
      x-data="{
          themeMode: {{ json_encode(old('theme_mode', $user->theme_mode ?: 'light')) }},
          themeColor: {{ json_encode(old('theme_color', $user->resolvedThemeColor())) }},
          themeBackground: {{ json_encode(old('theme_background', $user->resolvedThemeBackgroundKey())) }},
          previewUrl: {{ json_encode($user->avatarUrl()) }},
          removeAvatar: false,
          previewAvatar(event) {
              const file = event.target.files?.[0];
              if (!file) return;
              this.removeAvatar = false;
              this.previewUrl = URL.createObjectURL(file);
          }
      }">
    @csrf

    <div class="bg-white dark-panel rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Profile picture</h3>
            <p class="text-sm text-gray-500 mt-1">Upload a square JPG, PNG, or WEBP image up to 10 MB.</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-white shadow ring-2 ring-[var(--theme-primary)] shrink-0 bg-[var(--theme-primary)] flex items-center justify-center text-2xl font-bold" style="color: var(--theme-on-primary, #ffffff);">
                <template x-if="previewUrl && !removeAvatar">
                    <img :src="previewUrl" alt="Profile preview" class="w-full h-full object-cover">
                </template>
                <template x-if="!previewUrl || removeAvatar">
                    <span>{{ $user->initials() }}</span>
                </template>
            </div>
            <div class="space-y-2">
                <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/*"
                       @change="previewAvatar($event)"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[var(--theme-primary)] file:text-[color:var(--theme-on-primary,#ffffff)] file:text-sm file:font-medium file:border file:border-gray-300">
                @if($user->avatar_path)
                <label class="inline-flex items-center gap-2 text-sm text-red-600">
                    <input type="checkbox" name="remove_avatar" value="1" x-model="removeAvatar" class="rounded border-gray-300">
                    Remove current photo
                </label>
                @endif
                @error('avatar')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="bg-white dark-panel rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Account</h3>
            <p class="text-sm text-gray-500 mt-1">Update your display name or password.</p>
        </div>
        <label class="block text-sm font-medium text-gray-700">Display name
            <input name="name" value="{{ old('name', $user->name) }}" required
                   class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-[var(--theme-primary)] outline-none">
        </label>
        @error('name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="block text-sm font-medium text-gray-700">New password
                <input type="password" name="password" autocomplete="new-password"
                       class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-[var(--theme-primary)] outline-none"
                       placeholder="Leave blank to keep current">
            </label>
            <label class="block text-sm font-medium text-gray-700">Confirm password
                <input type="password" name="password_confirmation" autocomplete="new-password"
                       class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-[var(--theme-primary)] outline-none">
            </label>
        </div>
        @error('password')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="bg-white dark-panel rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Appearance</h3>
            <p class="text-sm text-gray-500 mt-1">Choose light or dark mode, accent color, and page background.</p>
        </div>

        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Mode</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                @foreach(['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label)
                <label class="cursor-pointer">
                    <input type="radio" name="theme_mode" value="{{ $value }}" class="sr-only peer" x-model="themeMode">
                    <span class="block text-center px-3 py-2.5 rounded-lg border text-sm font-medium peer-checked:border-[var(--theme-primary)] peer-checked:bg-[color-mix(in_srgb,var(--theme-primary)_12%,#e2e8f0)] peer-checked:text-[var(--theme-on-primary,#0f172a)] border-gray-200 text-gray-700">
                        {{ $label }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Theme color</p>
            <div class="flex flex-wrap gap-3">
                @foreach($themeColors as $hex => $label)
                <label class="cursor-pointer" title="{{ $label }}">
                    <input type="radio" name="theme_color" value="{{ $hex }}" class="sr-only peer" x-model="themeColor">
                    <span class="block w-10 h-10 rounded-full border-2 shadow ring-2 ring-transparent peer-checked:ring-offset-2 peer-checked:ring-gray-800 {{ strtolower($hex) === '#ffffff' ? 'border-gray-300' : 'border-white' }}"
                          style="background: {{ $hex }}"></span>
                </label>
                @endforeach
            </div>
            @error('theme_color')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Theme background</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($themeBackgrounds as $key => $bg)
                <label class="cursor-pointer">
                    <input type="radio" name="theme_background" value="{{ $key }}" class="sr-only peer" x-model="themeBackground">
                    <span class="block rounded-xl border-2 p-3 peer-checked:border-[var(--theme-primary)] border-gray-200">
                        @if(($bg['type'] ?? 'color') === 'image')
                            <span class="block h-20 rounded-lg mb-2 border border-black/5 bg-cover bg-center"
                                  style="background-image: linear-gradient(180deg, rgba(255,255,255,0.25), rgba(255,255,255,0.45)), url('{{ asset($bg['image']) }}')"></span>
                        @else
                            <span class="block h-12 rounded-lg mb-2 border border-black/5" style="background: {{ $bg['light'] }}"></span>
                        @endif
                        <span class="text-sm font-medium text-gray-800">{{ $bg['label'] }}</span>
                    </span>
                </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="submit" class="ui-btn-primary">
            Save profile settings
        </button>
        @if(auth()->user()?->canAccess('settings.view'))
        <a href="{{ route('settings.index') }}" class="ui-btn-secondary">
            System settings
        </a>
        @endif
    </div>
</form>
@endsection
