<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') - CSUCC Enrollment Analytics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    <script src="{{ asset('vendor/tailwind.js') }}"></script>
    <script defer src="{{ asset('vendor/alpine.min.js') }}"></script>
    <style>
        :root {
            --csucc-green: #0b5d3b;
            --csucc-gold: #d4a017;
            --theme-primary: #0b5d3b;
            --theme-on-primary: #ffffff;
            --font-ui: 'Source Sans 3', 'Segoe UI', sans-serif;
            --font-brand: 'Source Serif 4', Georgia, serif;
        }
        html, body {
            margin: 0;
            min-height: 100%;
        }
        body {
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            font-family: var(--font-ui);
            -webkit-font-smoothing: antialiased;
        }
        .font-brand { font-family: var(--font-brand); }
        .ui-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.7rem 1.1rem;
            border-radius: 0.7rem;
            font-size: 0.9375rem;
            font-weight: 600;
            background: var(--theme-primary);
            color: var(--theme-on-primary) !important;
            border: 1px solid color-mix(in srgb, var(--theme-primary) 75%, #000);
        }
        .ui-btn-primary:hover { filter: brightness(1.06); }
        .ui-input {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1px solid #d1d5db;
            border-radius: 0.7rem;
            font-size: 0.9375rem;
            outline: none;
            background: #fff;
        }
        .ui-input:focus {
            border-color: #0b5d3b;
            box-shadow: 0 0 0 3px rgba(11, 93, 59, 0.18);
        }
        [x-cloak] { display: none !important; }
        .login-green-blend {
            background: #031c14;
            mix-blend-mode: multiply;
            opacity: 0.92;
        }
        .login-holo {
            background:
                radial-gradient(ellipse at 12% 8%, rgba(170, 255, 210, 0.35), transparent 36%),
                radial-gradient(ellipse at 88% 92%, rgba(212, 160, 23, 0.3), transparent 40%),
                linear-gradient(122deg, transparent 18%, rgba(150, 255, 210, 0.42) 40%, rgba(255, 214, 120, 0.38) 50%, rgba(90, 220, 180, 0.34) 60%, transparent 82%);
            mix-blend-mode: soft-light;
        }
        #login-sparkles {
            position: fixed;
            inset: 0;
            z-index: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        .login-logo {
            position: relative;
            display: inline-flex;
        }
        .login-logo::before {
            content: "";
            position: absolute;
            z-index: -1;
            inset: -6%;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #064e32, #1f9d5a, #b6f5d0, #0b5d3b, #157a49, #064e32);
            filter: blur(7px);
            animation: login-glow-spin 5.5s linear infinite;
        }
        .login-glow {
            position: relative;
            overflow: hidden;
            border-radius: 1.6rem;
            padding: 2px;
            box-shadow: 0 0 18px rgba(31, 157, 90, 0.45), 0 0 40px rgba(11, 93, 59, 0.35);
            animation: login-glow-pulse 3.4s ease-in-out infinite;
        }
        .login-glow::before {
            content: "";
            position: absolute;
            inset: -70%;
            background: conic-gradient(from 0deg, #064e32, #1f9d5a, #b6f5d0, #0b5d3b, #157a49, #064e32);
            animation: login-glow-spin 4.5s linear infinite;
        }
        .login-card {
            position: relative;
            z-index: 1;
            overflow: hidden;
            border-radius: 1.45rem;
        }
        .login-card::after {
            content: "";
            position: absolute;
            z-index: 2;
            top: -20%;
            left: -70%;
            width: 38%;
            height: 140%;
            pointer-events: none;
            background: linear-gradient(100deg, transparent 0%, rgba(255, 255, 255, 0.15) 35%, rgba(255, 255, 255, 0.85) 50%, rgba(255, 255, 255, 0.15) 65%, transparent 100%);
            transform: skewX(-18deg);
            animation: login-shine 4.6s ease-in-out infinite;
        }
        @keyframes login-shine {
            0% { left: -70%; }
            22% { left: 140%; }
            100% { left: 140%; }
        }
        @keyframes login-glow-spin {
            to { transform: rotate(1turn); }
        }
        @keyframes login-glow-pulse {
            50% { box-shadow: 0 0 26px rgba(182, 245, 208, 0.55), 0 0 52px rgba(31, 157, 90, 0.4); }
        }
        @media (prefers-reduced-motion: reduce) {
            #login-sparkles { display: none; }
        }

        /* Global hover polish (applies across all guest pages) */
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
<body class="min-h-screen flex flex-col {{ request()->routeIs('login') ? 'lg:h-screen lg:overflow-hidden' : '' }}" x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false">
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 bg-cover bg-center bg-no-repeat"
         style="background-image: url('{{ asset('images/branding/campus-building.jpg') }}');"></div>
    @if(request()->routeIs('login'))
    <div aria-hidden="true" class="login-green-blend pointer-events-none fixed inset-0 -z-10"></div>
    <div aria-hidden="true" class="login-holo pointer-events-none fixed inset-0 -z-10"></div>
    <canvas id="login-sparkles" aria-hidden="true"></canvas>
    @else
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 bg-gradient-to-br from-[#053822]/90 via-[#0b5d3b]/85 to-[#0a2f22]/92"></div>
    @endif

    @unless(request()->routeIs('login'))
    <header class="sticky top-0 z-40 border-b border-white/10 bg-[#0a2f22]/80 backdrop-blur-md">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex h-16 items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3 min-w-0 shrink">
                    <img src="{{ asset('images/branding/logo.png') }}" alt="CSUCC" class="w-10 h-10 rounded-full object-contain bg-black/30 shrink-0 ring-1 ring-white/15">
                    <div class="min-w-0 hidden xs:block sm:block">
                        <p class="text-[10px] font-semibold tracking-[0.18em] text-amber-300/90">CSUCC · CEIT</p>
                        <p class="font-brand text-sm font-semibold text-white leading-tight truncate">Enrollment Analytics</p>
                    </div>
                </a>

                <nav class="hidden md:flex items-center gap-1" aria-label="Primary">
                    <a href="{{ route('home') }}"
                       class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('home') ? 'bg-white/15 text-white' : 'text-emerald-100/85 hover:bg-white/10 hover:text-white' }}">Home</a>
                    <a href="{{ route('about') }}"
                       class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('about') ? 'bg-white/15 text-white' : 'text-emerald-100/85 hover:bg-white/10 hover:text-white' }}">About</a>
                    <a href="{{ route('terms') }}"
                       class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('terms') ? 'bg-white/15 text-white' : 'text-emerald-100/85 hover:bg-white/10 hover:text-white' }}">Terms</a>
                </nav>

                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}"
                       class="hidden sm:inline-flex px-3 py-2 rounded-lg text-sm font-medium text-white/90 hover:bg-white/10 {{ request()->routeIs('login') ? 'ring-1 ring-white/20' : '' }}">Sign in</a>
                    <button type="button" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg text-white hover:bg-white/10"
                            @click="navOpen = !navOpen" :aria-expanded="navOpen" aria-controls="mobile-nav" aria-label="Open menu">
                        <svg x-show="!navOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="navOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div id="mobile-nav" x-show="navOpen" x-cloak x-transition class="md:hidden pb-4">
                <nav class="flex flex-col gap-1" aria-label="Mobile">
                    <a href="{{ route('home') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('home') ? 'bg-white/15 text-white' : 'text-emerald-100' }}">Home</a>
                    <a href="{{ route('login') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('login') ? 'bg-white/15 text-white' : 'text-emerald-100' }}">Sign in</a>
                    <a href="{{ route('about') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('about') ? 'bg-white/15 text-white' : 'text-emerald-100' }}">About</a>
                    <a href="{{ route('terms') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('terms') ? 'bg-white/15 text-white' : 'text-emerald-100' }}">Terms</a>
                </nav>
            </div>
        </div>
    </header>
    @endunless

    <main class="relative z-10 w-full flex-1 px-4 py-8 sm:py-10 {{ request()->routeIs('login') ? 'lg:flex lg:items-center lg:py-6 lg:min-h-0' : '' }}">
        <div class="mx-auto w-full @yield('container-class', 'max-w-md')">
            @yield('content')
        </div>
    </main>

    @unless(request()->routeIs('login'))
    <footer class="relative z-10 mt-auto border-t border-white/10 bg-[#071a14]/85 backdrop-blur-md">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/branding/logo.png') }}" alt="" class="w-11 h-11 rounded-full object-contain bg-black/30">
                        <div>
                            <p class="text-[10px] font-semibold tracking-wider text-amber-300/90">CSUCC</p>
                            <p class="text-sm font-semibold text-white leading-tight">Enrollment Analytics</p>
                        </div>
                    </div>
                    <p class="text-sm text-emerald-100/75 mt-4 leading-relaxed">
                        Decision support for the CEIT Program at Caraga State University Cabadbaran Campus.
                    </p>
                </div>

                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-amber-300/90 mb-3">Navigate</h2>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('home') }}" class="text-emerald-100/85 hover:text-white transition">Home</a></li>
                        <li><a href="{{ route('about') }}" class="text-emerald-100/85 hover:text-white transition">About</a></li>
                        <li><a href="{{ route('login') }}" class="text-emerald-100/85 hover:text-white transition">Sign in</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-amber-300/90 mb-3">Legal</h2>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('terms') }}" class="text-emerald-100/85 hover:text-white transition">Terms and Conditions</a></li>
                        <li><span class="text-emerald-100/60">CEIT use only</span></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-amber-300/90 mb-3">Campus</h2>
                    <p class="text-sm text-emerald-100/85 leading-relaxed">
                        Caraga State University<br>
                        Cabadbaran Campus<br>
                        Cabadbaran City, Agusan del Norte
                    </p>
                </div>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 py-4 flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
                <p class="text-xs text-emerald-100/60">&copy; {{ date('Y') }} CSUCC Cabadbaran Campus. All rights reserved.</p>
                <p class="text-xs text-emerald-100/50">Enrollment Analytics &amp; Decision Support</p>
            </div>
        </div>
    </footer>
    @else
    <footer class="relative z-10 mt-auto shrink-0 border-t border-white/10 bg-[#03140e]/80">
        <div class="mx-auto max-w-3xl px-4 py-3 text-center">
            <p class="text-sm tracking-wide text-white/90">Enrollment Analytics &amp; Decision Support for CEIT.</p>
            <p class="mt-1 text-[11px] tracking-wide text-white/60">&copy; {{ date('Y') }} CSUCC Cabadbaran Campus</p>
        </div>
    </footer>
    @endunless
    @auth
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    @endauth
    @if(request()->routeIs('login'))
    <script>
        (function () {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const canvas = document.getElementById('login-sparkles');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const sparks = [];
            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            resize();
            window.addEventListener('resize', resize);
            for (let i = 0; i < 42; i++) {
                sparks.push({
                    x: Math.random(),
                    y: Math.random(),
                    r: Math.random() * 1.4 + 0.5,
                    speed: Math.random() * 0.00035 + 0.00012,
                    drift: Math.random() * 0.4 + 0.2,
                    phase: Math.random() * Math.PI * 2,
                    gold: Math.random() > 0.5
                });
            }
            function star(x, y, radius, alpha, gold) {
                ctx.save();
                ctx.translate(x, y);
                ctx.fillStyle = gold
                    ? 'rgba(255, 214, 120, ' + alpha + ')'
                    : 'rgba(255, 255, 255, ' + alpha + ')';
                ctx.beginPath();
                for (let i = 0; i < 4; i++) {
                    ctx.lineTo(Math.cos(i * Math.PI / 2) * radius, Math.sin(i * Math.PI / 2) * radius);
                    ctx.lineTo(Math.cos(i * Math.PI / 2 + Math.PI / 4) * radius * 0.28, Math.sin(i * Math.PI / 2 + Math.PI / 4) * radius * 0.28);
                }
                ctx.closePath();
                ctx.fill();
                ctx.restore();
            }
            let frames = 0;
            function draw() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                for (const spark of sparks) {
                    spark.y -= spark.speed;
                    spark.x += Math.sin(spark.phase) * 0.00035;
                    spark.phase += spark.drift * 0.02;
                    if (spark.y < -0.03) {
                        spark.y = 1.03;
                        spark.x = Math.random();
                    }
                    const alpha = 0.25 + Math.abs(Math.sin(spark.phase)) * 0.75;
                    star(spark.x * canvas.width, spark.y * canvas.height, spark.r * 2.2, alpha, spark.gold);
                }
                frames++;
                canvas.dataset.frames = String(frames);
                requestAnimationFrame(draw);
            }
            draw();
        })();
    </script>
    @endif
    @if(session('logged_out'))
    <script>
        history.pushState(null, '', location.href);
        window.addEventListener('popstate', function () {
            history.pushState(null, '', location.href);
        });
    </script>
    @endif
</body>
</html>
