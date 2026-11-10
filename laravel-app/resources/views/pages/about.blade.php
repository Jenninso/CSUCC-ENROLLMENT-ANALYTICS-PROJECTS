@extends('layouts.guest')
@section('title', 'About')
@section('container-class', 'max-w-4xl')

@section('content')
<article class="bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-white/20 overflow-hidden">
    <div class="px-6 sm:px-10 py-8 sm:py-10 border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-amber-50/60">
        <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-800 font-semibold">CSUCC Cabadbaran Campus</p>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">About this system</h1>
        <p class="text-gray-600 mt-3 max-w-2xl leading-relaxed">
            Enrollment Analytics &amp; Decision Support is intended only for the College of Engineering and Information Technology (CEIT)
            and the BS Information Technology (BSIT) program. It helps authorized personnel understand enrollment patterns, plan capacity, and act on reliable data.
        </p>
    </div>

    <div class="px-6 sm:px-10 py-8 space-y-8 text-gray-700 leading-relaxed">
        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Purpose</h2>
            <p>
                The platform brings CEIT / BSIT enrollment records, analytics, forecasting, and alerts into one secure workspace.
                Administrators and approved staff can review BSIT trends by academic year, semester, and year level, generate reports,
                and use decision-support tools without relying on scattered spreadsheets.
            </p>
        </section>

        <div class="grid sm:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-[#0b5d3b]">Analytics</p>
                <p class="text-sm text-gray-600 mt-1">BSIT enrollment summaries, trends, retention, and year-level comparisons within CEIT.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-[#0b5d3b]">Forecasting</p>
                <p class="text-sm text-gray-600 mt-1">Historical enrollment used to project demand and support planning.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-[#0b5d3b]">Oversight</p>
                <p class="text-sm text-gray-600 mt-1">Role-based access, account approval, and activity logs for accountable use.</p>
            </div>
        </div>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Who may use it</h2>
            <p>
                This system is intended only for CEIT and the BSIT program. Access is limited to authorized Caraga State University Cabadbaran Campus personnel
                involved in CEIT / BSIT enrollment planning and reporting.
                New accounts must be registered with a valid campus identity, verified by email OTP, and approved by a Super Administrator or Administrator before sign-in is allowed.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Before you sign in</h2>
            <p>
                Please read the
                <a href="{{ route('terms') }}" class="text-emerald-800 font-medium underline underline-offset-2 hover:text-emerald-950">Terms and Conditions</a>
                so you understand acceptable use, privacy, and account responsibilities. You can review them without logging in.
            </p>
        </section>
    </div>
</article>
@endsection
