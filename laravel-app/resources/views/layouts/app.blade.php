@php
    $themeUser = auth()->user();
    $themeModePref = $themeUser?->theme_mode ?: 'light';
    $themeColor = $themeUser?->resolvedThemeColor() ?? '#ffffff';
    $themeSidebar = $themeUser?->themeSidebarColor() ?? '#ffffff';
    $themeOnPrimary = $themeUser?->themeOnPrimaryColor() ?? '#0f172a';
    $themeSidebarLight = $themeUser?->themeSidebarIsLight() ?? true;
    // Text links on light panels must stay readable when primary is white.
    $themeLink = $themeSidebarLight ? '#0b5d3b' : $themeColor;
    $themeBackgrounds = \App\Models\User::THEME_BACKGROUNDS;
    $themeBgKey = $themeUser?->resolvedThemeBackgroundKey() ?? 'sand';
    $themeBgLight = $themeBackgrounds[$themeBgKey]['light'] ?? '#f3f4f6';
    $themeBgDark = $themeBackgrounds[$themeBgKey]['dark'] ?? '#0f172a';
    $themeBgIsImage = $themeUser?->themeBackgroundIsImage() ?? false;
    $themeBgImageUrl = $themeUser?->themeBackgroundImageUrl();
@endphp
<!DOCTYPE html>
<html lang="en"
      data-theme-mode="{{ $themeModePref }}"
      x-data="{
          sidebarOpen: false,
          sidebarCollapsed: false,
          navQuery: '',
          navMatch(text) {
              const q = this.navQuery.trim().toLowerCase();
              if (!q) return true;
              return String(text).toLowerCase().includes(q);
          },
          submitNavSearch() {
              const q = this.navQuery.trim();
              if (!q) return;
              const nav = this.$refs.sideNav;
              const link = nav ? [...nav.querySelectorAll('a[href]')].find((item) => item.offsetParent !== null) : null;
              if (link) {
                  window.location.assign(link.href);
                  return;
              }
              if (this.$refs.recordSearch) {
                  window.location.assign(this.$refs.recordSearch.href);
              }
          },
          toggleSidebar() {
              if (window.matchMedia('(min-width: 1024px)').matches) {
                  this.sidebarCollapsed = !this.sidebarCollapsed;
                  localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
                  return;
              }
              this.sidebarOpen = !this.sidebarOpen;
          },
          themeMode: {{ json_encode($themeModePref) }},
          applyThemeMode() {
              const mode = this.themeMode === 'system'
                  ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                  : this.themeMode;
              document.documentElement.classList.toggle('dark', mode === 'dark');
              document.documentElement.dataset.resolvedTheme = mode;
          }
      }"
      x-init="
          applyThemeMode();
          this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === '1';
          window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => applyThemeMode());
      ">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - CSUCC Enrollment Analytics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { darkMode: 'class' };
        (function () {
            const pref = {{ json_encode($themeModePref) }};
            const dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <script src="{{ asset('vendor/tailwind.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="{{ asset('vendor/alpine.min.js') }}"></script>
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --csucc-green: #0b5d3b;
            --csucc-gold: #d4a017;
            --theme-primary: {{ $themeColor }};
            --theme-on-primary: {{ $themeOnPrimary }};
            --theme-link: {{ $themeLink }};
            --theme-sidebar: {{ $themeSidebar }};
            --theme-bg: {{ $themeBgLight }};
            --theme-panel: #ffffff;
            --theme-text: #111827;
            --theme-muted: #6b7280;
            --theme-border: #e5e7eb;
            --theme-radius: 0.75rem;
            --theme-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.06);
            --font-ui: 'Source Sans 3', 'Segoe UI', sans-serif;
            --font-brand: 'Source Serif 4', Georgia, serif;
        }
        html.dark {
            --theme-bg: {{ $themeBgDark }};
            --theme-panel: #1e293b;
            --theme-text: #e2e8f0;
            --theme-muted: #94a3b8;
            --theme-border: #334155;
            --theme-shadow: 0 1px 2px rgba(0, 0, 0, 0.2), 0 10px 28px rgba(0, 0, 0, 0.28);
        }

        body.app-shell {
            background: var(--theme-bg);
            color: var(--theme-text);
            font-family: var(--font-ui);
            -webkit-font-smoothing: antialiased;
        }
        .font-brand { font-family: var(--font-brand); }

        /* Shared UI primitives */
        .ui-panel {
            background: var(--theme-panel);
            color: var(--theme-text);
            border: 1px solid var(--theme-border);
            border-radius: var(--theme-radius);
            box-shadow: var(--theme-shadow);
        }
        .ui-kpi {
            background: var(--theme-panel);
            color: var(--theme-text);
            border: 1px solid var(--theme-border);
            border-radius: var(--theme-radius);
            box-shadow: var(--theme-shadow);
            border-left: 3px solid var(--theme-primary);
            padding: 1.15rem 1.25rem;
        }
        .ui-kpi-label {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--theme-muted);
        }
        .ui-kpi-value {
            margin-top: 0.35rem;
            font-size: 1.625rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--theme-text);
            line-height: 1.2;
        }
        .ui-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.55rem 1.1rem;
            border-radius: 0.65rem;
            font-size: 0.875rem;
            font-weight: 600;
            background: var(--theme-primary);
            color: var(--theme-on-primary) !important;
            border: 1px solid color-mix(in srgb, var(--theme-primary) 70%, #000 12%);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }
        .ui-btn-primary:hover { filter: brightness(0.96); }
        .ui-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.55rem 1.1rem;
            border-radius: 0.65rem;
            font-size: 0.875rem;
            font-weight: 600;
            background: var(--theme-panel);
            color: var(--theme-text) !important;
            border: 1px solid var(--theme-border);
        }
        .ui-btn-secondary:hover { background: color-mix(in srgb, var(--theme-bg) 70%, var(--theme-panel)); }
        .ui-link {
            color: var(--theme-link) !important;
            font-weight: 600;
        }
        .ui-link:hover { text-decoration: underline; }
        .ui-page-title {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--theme-text);
            line-height: 1.25;
        }
        .ui-page-sub {
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: var(--theme-muted);
            max-width: 42rem;
            line-height: 1.45;
        }
        .ui-input {
            width: 100%;
            padding: 0.55rem 0.75rem;
            border: 1px solid var(--theme-border);
            border-radius: 0.65rem;
            font-size: 0.875rem;
            background: var(--theme-panel);
            color: var(--theme-text);
            outline: none;
        }
        .ui-input:focus {
            border-color: color-mix(in srgb, var(--theme-primary) 55%, var(--theme-border));
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-primary) 18%, transparent);
        }
        .ui-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }
        .ui-table thead th {
            text-align: left;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--theme-muted);
            background: color-mix(in srgb, var(--theme-bg) 55%, var(--theme-panel));
            border-bottom: 1px solid var(--theme-border);
            padding: 0.75rem 0.85rem;
            white-space: nowrap;
        }
        .ui-table tbody td {
            padding: 0.75rem 0.85rem;
            border-top: 1px solid var(--theme-border);
            vertical-align: middle;
        }
        .ui-table tbody tr:hover {
            background: color-mix(in srgb, var(--theme-primary) 5%, var(--theme-panel));
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.75rem;
            border-radius: 0.65rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .theme-sidebar[data-sidebar-tone="dark"] .nav-link:not(.theme-primary-bg) {
            color: rgba(209, 250, 229, 0.82);
        }
        .theme-sidebar[data-sidebar-tone="dark"] .nav-link:not(.theme-primary-bg):hover {
            background: rgba(0, 0, 0, 0.18);
            color: #fff;
        }
        .theme-sidebar[data-sidebar-tone="dark"] .nav-link.theme-primary-bg {
            box-shadow: inset 3px 0 0 var(--csucc-gold);
        }
        @if($themeBgIsImage && $themeBgImageUrl)
        body.app-shell {
            background-color: var(--theme-bg);
            background-image:
                linear-gradient(180deg, rgba(255,255,255,0.42), rgba(255,255,255,0.58)),
                url('{{ $themeBgImageUrl }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        html.dark body.app-shell {
            background-image:
                linear-gradient(180deg, rgba(15,23,42,0.55), rgba(15,23,42,0.68)),
                url('{{ $themeBgImageUrl }}');
        }
        /* Frosted glass panels so campus background shows through */
        body.app-shell .bg-white,
        body.app-shell .dark-panel,
        body.app-shell .app-header {
            background: rgba(255, 255, 255, 0.62) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.12);
        }
        html.dark body.app-shell .bg-white,
        html.dark body.app-shell .dark-panel,
        html.dark body.app-shell .app-header {
            background: rgba(30, 41, 59, 0.58) !important;
            border-color: rgba(148, 163, 184, 0.28);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.28);
        }
        html.dark body.app-shell input,
        html.dark body.app-shell select,
        html.dark body.app-shell textarea {
            background-color: rgba(15, 23, 42, 0.55);
        }
        @endif
        .theme-sidebar { background: var(--theme-sidebar); }
        .theme-primary-bg {
            background: var(--theme-primary);
            color: var(--theme-on-primary);
        }
        .theme-primary-bg.text-white { color: var(--theme-on-primary) !important; }

        /* White theme: light sidebar with CSUCC green text */
        .theme-sidebar[data-sidebar-tone="light"] {
            color: #042f24;
            border-right: 1px solid #e2e8f0;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.06);
        }
        .theme-sidebar[data-sidebar-tone="light"] .border-emerald-900\/60 {
            border-color: #e2e8f0 !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] img {
            background: #f1f5f9 !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] .text-amber-300\/90,
        .theme-sidebar[data-sidebar-tone="light"] .text-emerald-200\/70 {
            color: #047857 !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] nav a,
        .theme-sidebar[data-sidebar-tone="light"] nav button {
            color: #042f24 !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] nav a:hover,
        .theme-sidebar[data-sidebar-tone="light"] nav button:hover {
            background: #f4f8f5 !important;
            color: #021c15 !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] nav .theme-primary-bg,
        .theme-sidebar[data-sidebar-tone="light"] nav .theme-primary-bg.text-white {
            background: #f4f8f5 !important;
            color: #042f24 !important;
            box-shadow: inset 3px 0 0 #042f24;
        }
        .theme-sidebar[data-sidebar-tone="light"] nav .text-amber-300,
        .theme-sidebar[data-sidebar-tone="light"] nav a.bg-emerald-950\/50,
        .theme-sidebar[data-sidebar-tone="light"] nav .bg-emerald-950\/50 {
            color: #042f24 !important;
            background: #f4f8f5 !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] nav .text-emerald-200\/60,
        .theme-sidebar[data-sidebar-tone="light"] nav .hover\:text-white:hover {
            color: #047857 !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] nav a.text-emerald-200\/60:hover {
            color: #064e3b !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] .sidebar-footer {
            border-color: #e2e8f0 !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] .sidebar-footer .text-emerald-200\/60 {
            color: #047857 !important;
        }
        .theme-sidebar[data-sidebar-tone="light"] .sidebar-search input {
            background: #ffffff;
            color: #042f24;
            border-color: #cbd5e1;
        }
        .theme-sidebar[data-sidebar-tone="light"] .sidebar-search input::placeholder {
            color: #64748b;
        }
        @media (min-width: 1024px) {
            aside.theme-sidebar.is-collapsed {
                width: 4.75rem !important;
                overflow-x: hidden;
            }
            aside.theme-sidebar.is-collapsed .sidebar-header {
                flex-direction: column;
                gap: 0.5rem;
                padding: 0.85rem 0.4rem;
            }
            aside.theme-sidebar.is-collapsed .sidebar-search,
            aside.theme-sidebar.is-collapsed .sidebar-brand,
            aside.theme-sidebar.is-collapsed .sidebar-user-meta,
            aside.theme-sidebar.is-collapsed nav .ml-8,
            aside.theme-sidebar.is-collapsed nav svg.w-4,
            aside.theme-sidebar.is-collapsed nav span.ml-auto {
                display: none !important;
            }
            aside.theme-sidebar.is-collapsed nav a,
            aside.theme-sidebar.is-collapsed nav button {
                justify-content: center !important;
                gap: 0 !important;
                font-size: 0 !important;
                padding-left: 0.45rem;
                padding-right: 0.45rem;
            }
            aside.theme-sidebar.is-collapsed nav svg.w-5 {
                width: 1.35rem;
                height: 1.35rem;
            }
            aside.theme-sidebar.is-collapsed .sidebar-footer {
                padding-left: 0.4rem;
                padding-right: 0.4rem;
            }
            aside.theme-sidebar.is-collapsed .sidebar-footer > .flex {
                justify-content: center;
            }
        }
        .app-header {
            background: color-mix(in srgb, var(--theme-panel) 92%, transparent);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-color: var(--theme-border);
        }
        .dark-panel {
            background: var(--theme-panel) !important;
            color: var(--theme-text);
            border-color: var(--theme-border);
        }
        html.dark .bg-white {
            background-color: var(--theme-panel) !important;
            color: var(--theme-text);
        }
        html.dark .bg-gray-50,
        html.dark .bg-gray-100 {
            background-color: color-mix(in srgb, var(--theme-bg) 85%, #000) !important;
        }
        html.dark .text-gray-800,
        html.dark .text-gray-900,
        html.dark .text-gray-700,
        html.dark .text-slate-800,
        html.dark .text-slate-900,
        html.dark .text-slate-700 {
            color: var(--theme-text) !important;
        }
        html.dark .text-gray-500,
        html.dark .text-gray-600,
        html.dark .text-slate-500,
        html.dark .text-slate-600 {
            color: var(--theme-muted) !important;
        }
        html.dark .border-gray-100,
        html.dark .border-gray-200,
        html.dark .border-gray-300,
        html.dark .border-slate-100,
        html.dark .border-slate-200,
        html.dark .border-slate-300 {
            border-color: var(--theme-border) !important;
        }
        html.dark .text-indigo-700,
        html.dark .text-indigo-600 {
            color: #a5b4fc !important;
        }
        html.dark .text-emerald-700,
        html.dark .text-emerald-600 {
            color: #6ee7b7 !important;
        }
        html.dark .text-amber-800,
        html.dark .text-amber-700 {
            color: #fcd34d !important;
        }
        html.dark .text-red-600,
        html.dark .text-red-700 {
            color: #fca5a5 !important;
        }
        html.dark .hover\:bg-gray-50:hover,
        html.dark .hover\:bg-gray-100:hover,
        html.dark .hover\:bg-slate-50:hover,
        html.dark .hover\:bg-indigo-50:hover,
        html.dark .hover\:bg-emerald-50:hover,
        html.dark .hover\:bg-amber-50:hover,
        html.dark .hover\:bg-red-50:hover {
            background-color: rgba(148, 163, 184, 0.14) !important;
        }
        html.dark input,
        html.dark select,
        html.dark textarea {
            background-color: #0f172a;
            color: var(--theme-text);
            border-color: var(--theme-border);
        }
        html.dark .shadow-sm,
        html.dark .shadow,
        html.dark .shadow-lg {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35) !important;
        }

        /* Global hover polish (applies across all authenticated pages) */
        @media (prefers-reduced-motion: reduce) {
            * {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
        a[href],
        button:not(:disabled) {
            transition: transform 160ms ease,
                        box-shadow 160ms ease,
                        background-color 160ms ease,
                        color 160ms ease,
                        border-color 160ms ease,
                        filter 160ms ease;
        }
        a[href]:hover {
            transform: translateY(-1px);
        }
        button:not(:disabled):hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.10);
        }
        button:not(:disabled):active,
        a[href]:active {
            transform: translateY(0);
        }
    </style>
</head>
<body class="app-shell min-h-screen">

    {{-- Flash Messages --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="fixed top-4 right-4 z-50 bg-emerald-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="fixed top-4 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Sidebar Overlay --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside data-sidebar-tone="{{ $themeSidebarLight ? 'light' : 'dark' }}"
           class="theme-sidebar fixed inset-y-0 left-0 z-50 w-64 {{ $themeSidebarLight ? 'text-[#042f24]' : 'text-white' }} transform transition-all duration-200 flex flex-col"
           :class="[sidebarOpen ? 'translate-x-0' : 'max-lg:-translate-x-full', sidebarCollapsed && 'is-collapsed']">
        {{-- Logo --}}
        <div class="sidebar-header flex items-center gap-3 px-4 py-5 border-b border-emerald-900/60">
            <img src="{{ asset('images/branding/logo.png') }}" alt="CSUCC" class="w-11 h-11 rounded-full object-contain bg-black/30 shrink-0 ring-1 ring-white/15">
            <div class="sidebar-brand min-w-0 flex-1">
                <p class="text-[10px] font-semibold tracking-[0.18em] text-amber-300/90">CSUCC</p>
                <h1 class="font-brand text-sm font-semibold leading-tight">
                    <span class="block">Enrollment</span>
                    <span class="block">Analytics</span>
                </h1>
                <p class="text-[10px] text-emerald-200/70 leading-tight mt-0.5">CEIT Program</p>
            </div>
            <button type="button" @click="toggleSidebar()" class="shrink-0 p-1.5 rounded-lg hover:bg-black/20" aria-label="Collapse sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
        </div>

        @php $navUser = auth()->user(); @endphp
        <form class="sidebar-search px-3 pt-3" @submit.prevent="submitNavSearch()">
            <label class="relative block">
                <span class="sr-only">Search</span>
                <input x-model="navQuery" type="search" placeholder="Search menu or records" autocomplete="off"
                       class="w-full rounded-lg bg-black/20 border border-white/15 text-sm px-3 py-2 pr-9 placeholder:text-emerald-100/50 focus:outline-none focus:ring-2 focus:ring-white/30">
                <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 p-1 rounded-md hover:bg-black/20" aria-label="Search">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"/></svg>
                </button>
            </label>
            @if($navUser?->canAccess('enrollment.view'))
            <div x-show="navQuery.trim()" x-cloak class="mt-2 space-y-1">
                <a x-ref="recordSearch" :href="@js(route('students.index')) + '?q=' + encodeURIComponent(navQuery.trim())" class="block px-2 py-1.5 rounded-lg text-xs hover:bg-black/20">Search students</a>
                <a :href="@js(route('spes.index')) + '?q=' + encodeURIComponent(navQuery.trim())" class="block px-2 py-1.5 rounded-lg text-xs hover:bg-black/20">Search SPR</a>
            </div>
            @endif
        </form>
        {{-- Navigation --}}
        <nav x-ref="sideNav" class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            {{-- Dashboard --}}
            <a x-show="navMatch('dashboard')" href="{{ route('dashboard') }}" title="Dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Dashboard
            </a>

            @if($navUser?->canAccess('enrollment.view'))
            <div x-data="{ open: {{ request()->routeIs('students.*') ? 'true' : 'false' }} }" x-show="navMatch('student records') || navMatch('import file')" x-effect="if (navQuery.trim()) open = true">
                <button @click="if ($root.sidebarCollapsed && window.matchMedia('(min-width: 1024px)').matches) { $root.sidebarCollapsed = false; localStorage.setItem('sidebarCollapsed', '0'); open = true; } else { open = !open }" title="Student Records" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('students.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/></svg>
                        Student Records
                    </span>
                    <svg :class="open && 'rotate-90'" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open" x-cloak class="ml-8 mt-1 space-y-1">
                    <a x-show="!navQuery.trim() || navMatch('student records') || navMatch('records')" href="{{ route('students.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('students.index') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Records</a>
                    @if($navUser->canAccess('enrollment.manage'))
                    <a x-show="!navQuery.trim() || navMatch('import')" href="{{ route('students.import') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('students.import') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Import File</a>
                    @endif
                </div>
            </div>
            @endif

            @if($navUser?->canAccess('enrollment.view'))
            <a x-show="navMatch('spr')" href="{{ route('spes.index') }}" title="SPR" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('spes.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                SPR
            </a>
            @endif

            @if($navUser?->canAccess('enrollment.view'))
            <a x-show="navMatch('at-risk') || navMatch('risk')" href="{{ route('at-risk.index') }}" title="At-risk students" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('at-risk.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008v.008H12V16.5zM3.75 6.75A2.25 2.25 0 016 4.5h12a2.25 2.25 0 012.25 2.25v10.5A2.25 2.25 0 0118 19.5H6a2.25 2.25 0 01-2.25-2.25V6.75z"/></svg>
                At-Risk
            </a>
            @endif

            @if($navUser?->canAccess('enrollment.view'))
            {{-- Enrollment --}}
            <div x-data="{ open: {{ request()->routeIs('enrollment.*') ? 'true' : 'false' }} }" x-show="navMatch('enrollment') || navMatch('data quality') || navMatch('records')" x-effect="if (navQuery.trim()) open = true">
                <button @click="if ($root.sidebarCollapsed && window.matchMedia('(min-width: 1024px)').matches) { $root.sidebarCollapsed = false; localStorage.setItem('sidebarCollapsed', '0'); open = true; } else { open = !open }" title="Enrollment" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-emerald-100/80 hover:bg-black/20">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                        Enrollment
                    </span>
                    <svg :class="open && 'rotate-90'" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open" x-cloak class="ml-8 mt-1 space-y-1">
                    <a x-show="!navQuery.trim() || navMatch('enrollment') || navMatch('records')" href="{{ route('enrollment.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('enrollment.index') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Records</a>
                    <a x-show="!navQuery.trim() || navMatch('quality') || navMatch('enrollment')" href="{{ route('enrollment.quality') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('enrollment.quality') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Data Quality</a>
                </div>
            </div>
            @endif

            @if($navUser?->canAccess('analytics.view'))
            {{-- Analytics --}}
            <div x-data="{ open: {{ request()->routeIs('analytics.*') ? 'true' : 'false' }} }" x-show="navMatch('analytics') || navMatch('overview') || navMatch('trends') || navMatch('programs') || navMatch('colleges') || navMatch('retention')" x-effect="if (navQuery.trim()) open = true">
                <button @click="if ($root.sidebarCollapsed && window.matchMedia('(min-width: 1024px)').matches) { $root.sidebarCollapsed = false; localStorage.setItem('sidebarCollapsed', '0'); open = true; } else { open = !open }" title="Analytics" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-emerald-100/80 hover:bg-black/20">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                        Analytics
                    </span>
                    <svg :class="open && 'rotate-90'" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open" x-cloak class="ml-8 mt-1 space-y-1">
                    <a x-show="!navQuery.trim() || navMatch('analytics') || navMatch('overview')" href="{{ route('analytics.summary') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('analytics.summary') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Overview</a>
                    <a x-show="!navQuery.trim() || navMatch('analytics') || navMatch('trends')" href="{{ route('analytics.trends') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('analytics.trends') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Trends</a>
                    <a x-show="!navQuery.trim() || navMatch('analytics') || navMatch('programs')" href="{{ route('analytics.programs') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('analytics.programs') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Programs</a>
                    <a x-show="!navQuery.trim() || navMatch('analytics') || navMatch('colleges')" href="{{ route('analytics.colleges') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('analytics.colleges') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Colleges</a>
                    <a x-show="!navQuery.trim() || navMatch('analytics') || navMatch('retention')" href="{{ route('analytics.retention') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('analytics.retention') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Retention</a>
                </div>
            </div>
            @endif

            @if($navUser?->canAccess('forecasting.view'))
            {{-- Forecasting --}}
            <a x-show="navMatch('forecast')" href="{{ route('forecasting.index') }}" title="Forecasting" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('forecasting.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Forecasting
            </a>
            @endif

            @if($navUser?->canAccess('analytics.view'))
            {{-- AI Decision Support --}}
            <a x-show="navMatch('executive')" href="{{ route('executive') }}" title="Executive View" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('executive') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                Executive View
            </a>
            @endif
            @if($navUser?->canAccess('ai.view'))
            <a x-show="navMatch('ai decision') || navMatch('decision support')" href="{{ route('ai-support.index') }}" title="AI Decision Support" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('ai-support.index') || request()->routeIs('ai-support.show') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 00.659 1.591L19 14.5M14.25 3.104c.251.023.501.05.75.082M19 14.5l-2.47 2.47a2.25 2.25 0 01-1.59.659H9.06a2.25 2.25 0 01-1.591-.659L5 14.5m14 0V5a2 2 0 00-2-2H7a2 2 0 00-2 2v9.5"/></svg>
                AI Decision Support
            </a>
            <a x-show="navMatch('what-if') || navMatch('what if')" href="{{ route('ai-support.what-if') }}" title="What-If Analysis" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('ai-support.what-if') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                What-If Analysis
            </a>
            @endif

            @if($navUser?->canAccess('alerts.view'))
            {{-- Alerts --}}
            <a x-show="navMatch('alert')" href="{{ route('alerts.index') }}" title="Alerts" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('alerts.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                Alerts
            </a>
            @endif

            @if($navUser?->canAccess('reports.view'))
            {{-- Reports --}}
            <a x-show="navMatch('report')" href="{{ route('reports.index') }}" title="Reports" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('reports.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                Reports
            </a>
            @endif

            @if($navUser?->canAccess('data.manage'))
            {{-- Data Management --}}
            <div x-data="{ open: {{ request()->routeIs('data-management.*') ? 'true' : 'false' }} }" x-show="navMatch('data management') || navMatch('academic') || navMatch('semester') || navMatch('college') || navMatch('department') || navMatch('program')" x-effect="if (navQuery.trim()) open = true">
                <button @click="if ($root.sidebarCollapsed && window.matchMedia('(min-width: 1024px)').matches) { $root.sidebarCollapsed = false; localStorage.setItem('sidebarCollapsed', '0'); open = true; } else { open = !open }" title="Data Management" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-emerald-100/80 hover:bg-black/20">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75"/></svg>
                        Data Management
                    </span>
                    <svg :class="open && 'rotate-90'" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open" x-cloak class="ml-8 mt-1 space-y-1">
                    <a x-show="!navQuery.trim() || navMatch('data management') || navMatch('academic')" href="{{ route('data-management.academic-years.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('data-management.academic-years.*') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Academic Years</a>
                    <a x-show="!navQuery.trim() || navMatch('data management') || navMatch('semester')" href="{{ route('data-management.semesters.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('data-management.semesters.*') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Semesters</a>
                    <a x-show="!navQuery.trim() || navMatch('data management') || navMatch('college')" href="{{ route('data-management.colleges.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('data-management.colleges.*') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Colleges</a>
                    <a x-show="!navQuery.trim() || navMatch('data management') || navMatch('department')" href="{{ route('data-management.departments.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('data-management.departments.*') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Departments</a>
                    <a x-show="!navQuery.trim() || navMatch('data management') || navMatch('program')" href="{{ route('data-management.programs.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('data-management.programs.*') ? 'text-amber-300 bg-emerald-950/50' : 'text-emerald-200/60 hover:text-white' }}">Programs</a>
                </div>
            </div>
            @endif

            @if($navUser?->canAccess('users.manage'))
            <a x-show="navMatch('account')" href="{{ route('users.index') }}" title="Accounts" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('users.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                Accounts
            </a>
            @endif
            @if($navUser?->canAccess('users.approve'))
            <a x-show="navMatch('approval')" href="{{ route('approvals.index') }}" title="Approvals" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('approvals.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Approvals
                @if(($pendingRegistrations ?? 0) > 0)
                    <span class="ml-auto bg-amber-400 text-slate-900 text-[10px] font-bold px-1.5 py-0.5 rounded">{{ $pendingRegistrations }}</span>
                @endif
            </a>
            @endif
            @if($navUser?->isSuperAdmin())
            <a x-show="navMatch('delete')" href="{{ route('delete-requests.index') }}" title="Delete Requests" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('delete-requests.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                Delete Requests
                @if(($pendingDeletes ?? 0) > 0)
                    <span class="ml-auto bg-red-400 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">{{ $pendingDeletes }}</span>
                @endif
            </a>
            <a x-show="navMatch('log')" href="{{ route('audit-logs.index') }}" title="Logs" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('audit-logs.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Logs
            </a>
            @endif
            @if($navUser?->canAccess('logs.view') && ! $navUser?->isSuperAdmin())
            <a x-show="navMatch('login') || navMatch('log')" href="{{ route('login-logs.index') }}" title="Login Logs" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('login-logs.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Login Logs
            </a>
            @endif

            @if($navUser?->canAccess('settings.view'))
            {{-- Settings --}}
            <a x-show="navMatch('setting')" href="{{ route('settings.index') }}" title="Settings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('settings.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
            @endif
            <a x-show="navMatch('profile')" href="{{ route('profile.edit') }}" title="Profile" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('profile.*') ? 'theme-primary-bg text-white' : 'text-emerald-100/80 hover:bg-black/20' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                Profile
            </a>
        </nav>

        {{-- User Info at Bottom --}}
        <div class="sidebar-footer px-4 py-3.5 border-t border-white/10">
            <div class="flex items-center gap-3">
                @if($navUser?->avatarUrl())
                    <img src="{{ $navUser->avatarUrl() }}" alt="" class="w-9 h-9 rounded-full object-cover ring-2 ring-white/20">
                @else
                    <div class="w-9 h-9 theme-primary-bg rounded-full flex items-center justify-center text-sm font-bold">
                        {{ $navUser?->initials() ?? 'U' }}
                    </div>
                @endif
                <div class="sidebar-user-meta flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate">{{ $navUser->fullName() }}</p>
                    <p class="text-xs text-emerald-200/60 truncate">{{ $navUser->role->role_name ?? 'User' }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="flex flex-col min-h-screen min-w-0 transition-[padding] duration-200" :class="sidebarCollapsed ? 'lg:pl-[4.75rem]' : 'lg:pl-64'">
        {{-- Top Bar --}}
        <header class="app-header border-b sticky top-0 z-30">
            <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 py-3.5">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" @click="toggleSidebar()" class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-black/5" aria-label="Open sidebar">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-400 hidden sm:block">CSUCC · CEIT</p>
                        <h2 class="text-lg font-semibold text-gray-800 truncate leading-tight">@yield('title', 'Dashboard')</h2>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    @if($navUser?->canAccess('alerts.view'))
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" class="relative p-2 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-black/5" title="Alerts" aria-label="Alerts">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                            @if(isset($unreadAlerts) && $unreadAlerts > 0)
                            <span class="absolute top-1 right-1 min-w-[1rem] h-4 px-1 bg-red-500 rounded-full text-[10px] font-bold text-white flex items-center justify-center">{{ $unreadAlerts }}</span>
                            @endif
                        </button>
                        <div x-show="open" x-cloak @click.away="open = false" class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] ui-panel z-50 overflow-hidden">
                            <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-800">Notifications</p>
                                <a href="{{ route('alerts.index') }}" class="text-xs font-medium text-indigo-600">View all</a>
                            </div>
                            @forelse($recentAlerts ?? [] as $notice)
                            <a href="{{ route('alerts.show', $notice) }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-50">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ $notice->severity }}</p>
                                <p class="text-sm font-medium text-gray-800">{{ $notice->title }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $notice->created_at?->diffForHumans() }}</p>
                            </a>
                            @empty
                            <p class="px-4 py-6 text-sm text-gray-400 text-center">No unread alerts.</p>
                            @endforelse
                        </div>
                    </div>
                    @endif
                    {{-- User Dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-lg hover:bg-black/5 text-gray-700">
                            @if($navUser?->avatarUrl())
                                <img src="{{ $navUser->avatarUrl() }}" alt="" class="w-8 h-8 rounded-full object-cover ring-1 ring-black/10">
                            @else
                                <div class="w-8 h-8 theme-primary-bg rounded-full flex items-center justify-center text-sm font-bold">
                                    {{ $navUser?->initials() ?? 'U' }}
                                </div>
                            @endif
                            <span class="hidden md:block text-sm font-medium max-w-[9rem] truncate">{{ $navUser?->fullName() }}</span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak @click.away="open = false" class="absolute right-0 mt-2 w-56 ui-panel py-1.5 z-50 overflow-hidden">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $navUser?->fullName() }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $navUser?->role->role_name ?? 'User' }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile & Appearance</a>
                            @if($navUser?->canAccess('settings.view'))
                            <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">System Settings</a>
                            @endif
                            <hr class="my-1 border-gray-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @yield('content')
        </main>
    </div>

    @include('partials.ai-assistant')

    @stack('scripts')
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
