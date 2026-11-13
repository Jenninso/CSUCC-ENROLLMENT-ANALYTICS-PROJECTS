@extends('layouts.spr-public')

@php
    $locked = $locked ?? false;
    $studentMode = true;
@endphp

@section('content')
<div class="bar">
    <h1 style="font-size: 1.25rem; margin: 0;">Student Personal Record</h1>
    <p style="margin: 6px 0 0; color: #444; font-size: 0.9rem;">This link opens only the form. Fill the blank lines. The office section stays blank.</p>
    @unless($locked)
        <p style="margin-top: 12px; background: #fffbeb; border: 1px solid #f59e0b; color: #78350f; border-radius: 8px; padding: 12px 14px; font-size: 0.95rem; font-weight: 600;">Double-check every answer before you save. After you click Save SPR, you cannot edit this form.</p>
        <p style="margin-top: 12px; background: #fffbeb; border: 1px solid #f59e0b; color: #78350f; border-radius: 8px; padding: 12px 14px; font-size: 0.95rem; font-weight: 600;">For more concerns, please visit the Office of OASFA.</p>
    @endunless
    @if(session('success'))
        <p style="margin-top: 12px; background: #ecfdf5; border: 1px solid #6ee7b7; color: #064e3b; border-radius: 8px; padding: 10px 12px; font-size: 0.9rem;">{{ session('success') }} @if($application) Reference <strong>{{ $application->reference_no }}</strong>.@endif</p>
    @endif
    @if(session('warning'))
        <p style="margin-top: 12px; background: #fffbeb; border: 1px solid #f59e0b; color: #78350f; border-radius: 8px; padding: 12px 14px; font-size: 0.95rem; font-weight: 600;">{{ session('warning') }}</p>
    @endif
    @if(session('error'))
        <p style="margin-top: 12px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 10px 12px; font-size: 0.9rem;">{{ session('error') }}</p>
    @endif
    @if($errors->has('spr_duplicate'))
        <p style="margin-top: 12px; background: #fffbeb; border: 1px solid #f59e0b; color: #78350f; border-radius: 8px; padding: 12px 14px; font-size: 0.95rem; font-weight: 600;">{{ $errors->first('spr_duplicate') }}</p>
    @endif
    @if($errors->any())
        @php($fieldErrors = collect($errors->getMessages())->except('spr_duplicate')->flatten())
        @if($fieldErrors->isNotEmpty())
        <div style="margin-top: 12px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 10px 12px; font-size: 0.9rem;">
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach($fieldErrors as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif
    @endif
</div>

@if($locked && $application)
    <div class="bar" style="padding-bottom: 24px;">
        <p style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 10px 12px;">This form is saved. You cannot edit it.</p>
    </div>
    @include('spes.paper', ['editing' => false, 'studentMode' => true])
@else
    <form id="spr-form" method="POST" action="{{ route('spr.public.store') }}" class="sheet-guard" onsubmit="return confirm('Double-check your information. After you save, you cannot edit this form. Save now?');">
        @csrf
        <input type="hidden" name="status" value="submitted">
        @include('spes.paper', ['editing' => true, 'studentMode' => true])
        <div class="bar" style="padding-bottom: 28px;">
            <button type="submit" style="background: #0b5d3b; color: #fff; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer;">Save SPR</button>
        </div>
    </form>
@endif
@endsection
