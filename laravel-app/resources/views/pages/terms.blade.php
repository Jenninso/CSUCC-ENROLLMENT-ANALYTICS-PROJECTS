@extends('layouts.guest')
@section('title', 'Terms and Conditions')
@section('container-class', 'max-w-4xl')

@section('content')
<article class="bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-white/20 overflow-hidden">
    <div class="px-6 sm:px-10 py-8 sm:py-10 border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-amber-50/60">
        <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-800 font-semibold">Legal</p>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">Terms and Conditions</h1>
        <p class="text-gray-600 mt-3 max-w-2xl leading-relaxed">
            Please read these terms before creating an account or signing in to the CSUCC Enrollment Analytics &amp; Decision Support system.
            By registering or using the system, you agree to follow them.
        </p>
        <p class="text-xs text-gray-500 mt-4">Last updated: September 2026</p>
    </div>

    <div class="px-6 sm:px-10 py-8 space-y-8 text-gray-700 leading-relaxed text-[15px]">
        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">1. Acceptance of terms</h2>
            <p>
                These Terms and Conditions govern access to and use of the Enrollment Analytics &amp; Decision Support system
                operated for Caraga State University Cabadbaran Campus (CSUCC). The system is intended only for the
                College of Engineering and Information Technology (CEIT) Program.
                If you do not agree, do not register or sign in.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">2. Authorized users</h2>
            <p>
                The system is for authorized CSUCC personnel involved with the CEIT Program only.
                You must provide accurate identity details (including employee ID, name, and email).
                Accounts are subject to email verification and administrator approval. Access may be limited by assigned roles and permissions.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">3. Account security</h2>
            <ul class="list-disc pl-5 space-y-1.5">
                <li>Keep your password, OTP codes, and security-question answers confidential.</li>
                <li>Do not share your account or allow others to sign in as you.</li>
                <li>Notify an administrator promptly if you suspect unauthorized use.</li>
                <li>Accounts that are deactivated, blocked, or pending approval cannot be used to access the system.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">4. Acceptable use</h2>
            <p>You agree to use the system only for official CEIT Program academic planning, enrollment analysis, reporting, and related duties. You must not:</p>
            <ul class="list-disc pl-5 space-y-1.5 mt-2">
                <li>Attempt to access data, modules, or accounts you are not authorized to use.</li>
                <li>Upload or enter false, misleading, or unauthorized enrollment data.</li>
                <li>Interfere with system operation, security, logs, or other users’ work.</li>
                <li>Export, copy, or disclose institutional data except as allowed by your role and campus policy.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">5. Data and privacy</h2>
            <p>
                Enrollment records and related analytics may include personal and institutional information.
                Use and disclosure of this data must comply with university policy and applicable Philippine data-privacy laws,
                including the Data Privacy Act of 2012. Do not download or share reports outside authorized channels.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">6. Accuracy of information</h2>
            <p>
                Analytics, forecasts, and alerts depend on the quality of stored enrollment data.
                Outputs are decision-support tools, not a substitute for official registrar records or administrative judgment.
                Users responsible for data entry should review imports and records for completeness and correctness.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">7. Monitoring and records</h2>
            <p>
                Sign-in activity, account changes, and significant actions may be logged for security, audit, and accountability.
                Administrators may review logs, suspend accounts, or revoke access when policy or these terms are violated.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">8. Availability</h2>
            <p>
                The system is provided for campus operations and may be unavailable during maintenance, outages, or updates.
                CSUCC does not guarantee uninterrupted access.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">9. Changes to these terms</h2>
            <p>
                These terms may be updated as the system or campus policy changes.
                Continued use after updates are posted on this page constitutes acceptance of the revised terms.
                Check this page before registering or when you are asked to review the conditions of use.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">10. Contact</h2>
            <p>
                For questions about access, account approval, or these terms, contact your campus administrator
                or the office responsible for Enrollment Analytics at CSUCC Cabadbaran Campus.
            </p>
        </section>

        <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-4 sm:px-5">
            <p class="text-sm text-emerald-950">
                Return to
                <a href="{{ route('home') }}" class="font-semibold underline underline-offset-2">Home</a>
                for system details,
                <a href="{{ route('login') }}" class="font-semibold underline underline-offset-2">sign in</a>,
                or
                <a href="{{ route('register') }}" class="font-semibold underline underline-offset-2">create an account</a>.
            </p>
        </div>
    </div>
</article>
@endsection
