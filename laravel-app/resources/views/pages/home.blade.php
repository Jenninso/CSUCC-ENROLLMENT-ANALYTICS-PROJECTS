@extends('layouts.guest')
@section('title', 'Home')
@section('container-class', 'max-w-6xl')

@section('content')
<div class="space-y-8">
    <section class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md px-6 sm:px-10 py-10 sm:py-14 text-center sm:text-left">
        <div class="flex flex-col sm:flex-row sm:items-center gap-6">
            <img src="{{ asset('images/branding/logo.png') }}" alt="CSUCC Logo"
                 class="w-24 h-24 mx-auto sm:mx-0 object-contain drop-shadow-lg rounded-full bg-black/20 p-1 shrink-0">
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-[0.2em] text-amber-300/90 font-semibold">Caraga State University · Cabadbaran Campus</p>
                <h1 class="text-3xl sm:text-4xl font-bold text-white mt-2 leading-tight">Enrollment Analytics &amp; Decision Support</h1>
                <p class="text-emerald-100/90 mt-3 max-w-3xl leading-relaxed">
                    A secure workspace for enrollment records, analytics, forecasting, alerts, and reports —
                    intended only for the College of Engineering and Information Technology (CEIT) Program.
                    Review how the system works, who may use it, and the terms of use before you sign in.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center sm:justify-start gap-3">
                    <a href="{{ route('login') }}" class="inline-flex px-5 py-2.5 rounded-lg text-sm font-semibold bg-white text-[#0a2f22] hover:bg-emerald-50 transition">Sign in</a>
                    <a href="{{ route('terms') }}" class="inline-flex px-5 py-2.5 rounded-lg text-sm font-medium text-white border border-white/30 hover:bg-white/10 transition">Read terms</a>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-white/20 overflow-hidden">
        <div class="px-6 sm:px-10 py-8 sm:py-10 border-b border-gray-100">
            <h2 class="text-2xl font-bold text-gray-900">What this system provides</h2>
            <p class="text-gray-600 mt-2 max-w-3xl">Authorized CEIT Program personnel can use one platform instead of scattered spreadsheets. Access to each module depends on the role assigned after your account is approved.</p>
        </div>
        <div class="px-6 sm:px-10 py-8 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <h3 class="font-semibold text-[#0b5d3b]">Dashboard</h3>
                <p class="text-sm text-gray-600 mt-2">CEIT Program enrollment snapshot with filters by academic year, semester, and year level.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <h3 class="font-semibold text-[#0b5d3b]">Enrollment records</h3>
                <p class="text-sm text-gray-600 mt-2">View, add, import, and check data quality of enrollment files used for analysis and reporting.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <h3 class="font-semibold text-[#0b5d3b]">Analytics</h3>
                <p class="text-sm text-gray-600 mt-2">Enrollment trends, year-level comparisons, retention, and summary views for CEIT Program planning.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <h3 class="font-semibold text-[#0b5d3b]">Forecasting</h3>
                <p class="text-sm text-gray-600 mt-2">Historical enrollment used to project demand and support capacity and program planning.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <h3 class="font-semibold text-[#0b5d3b]">AI decision support</h3>
                <p class="text-sm text-gray-600 mt-2">Executive summaries, insights, and what-if analysis to explore planning scenarios.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <h3 class="font-semibold text-[#0b5d3b]">Alerts and reports</h3>
                <p class="text-sm text-gray-600 mt-2">Notices on notable changes plus generated reports for official review and documentation.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <h3 class="font-semibold text-[#0b5d3b]">Data management</h3>
                <p class="text-sm text-gray-600 mt-2">Maintain academic years, semesters, and the CEIT Program structure that organize the records.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <h3 class="font-semibold text-[#0b5d3b]">Accounts and oversight</h3>
                <p class="text-sm text-gray-600 mt-2">Role-based access, registration approval, activity logs, and settings for accountable campus use.</p>
            </div>
        </div>
    </section>

    <section class="bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-white/20 px-6 sm:px-10 py-8 sm:py-10">
        <h2 class="text-2xl font-bold text-gray-900">How to get access</h2>
        <p class="text-gray-600 mt-2">You can complete these steps only after you understand the terms. Sign-in is not available until an administrator approves your account.</p>
        <ol class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <li class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Step 1</p>
                <p class="font-semibold text-gray-900 mt-1">Read the terms</p>
                <p class="text-sm text-gray-600 mt-2">Open <a href="{{ route('terms') }}" class="text-emerald-800 font-medium underline underline-offset-2">Terms and Conditions</a> and review acceptable use, privacy, and account rules.</p>
            </li>
            <li class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Step 2</p>
                <p class="font-semibold text-gray-900 mt-1">Register</p>
                <p class="text-sm text-gray-600 mt-2">Create an account with your employee ID, name, campus email, password, and recovery questions.</p>
            </li>
            <li class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Step 3</p>
                <p class="font-semibold text-gray-900 mt-1">Verify email</p>
                <p class="text-sm text-gray-600 mt-2">Enter the one-time PIN sent to your email so the campus can confirm the address belongs to you.</p>
            </li>
            <li class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Step 4</p>
                <p class="font-semibold text-gray-900 mt-1">Wait for approval</p>
                <p class="text-sm text-gray-600 mt-2">A Super Administrator or Administrator must approve the account before you can sign in.</p>
            </li>
        </ol>
    </section>

    <div class="grid lg:grid-cols-2 gap-8">
        <section class="bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-white/20 px-6 sm:px-8 py-8">
            <h2 class="text-xl font-bold text-gray-900">Who may use it</h2>
            <p class="text-gray-600 mt-3 leading-relaxed">
                This system is intended only for the CEIT Program at Caraga State University Cabadbaran Campus.
                It is limited to authorized personnel; students and the public cannot browse enrollment data here. After approval, what you can see and change follows your assigned role and permissions.
            </p>
            <p class="text-gray-600 mt-3 leading-relaxed">
                Learn more on the <a href="{{ route('about') }}" class="text-emerald-800 font-medium underline underline-offset-2">About</a> page.
            </p>
        </section>
        <section class="bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-white/20 px-6 sm:px-8 py-8">
            <h2 class="text-xl font-bold text-gray-900">Data responsibility</h2>
            <p class="text-gray-600 mt-3 leading-relaxed">
                Enrollment records may include personal and institutional information. Use them only for official planning and reporting.
                Do not share logins, export data outside authorized channels, or treat forecasts as a replacement for official registrar records.
            </p>
            <p class="text-gray-600 mt-3 leading-relaxed">
                Full conditions are in the <a href="{{ route('terms') }}" class="text-emerald-800 font-medium underline underline-offset-2">Terms and Conditions</a>.
            </p>
        </section>
    </div>
</div>
@endsection
