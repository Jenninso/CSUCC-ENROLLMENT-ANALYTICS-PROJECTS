@extends('layouts.spr-public')

@php
    $locked = $locked ?? false;
    $studentMode = true;
@endphp

@section('content')
<div class="bar">
    <div class="spr-top">
        <h1>Student Personal Record</h1>
        <p class="lead">Fill the blank lines. The office section stays blank.</p>
        @unless($locked)
            <ul class="spr-notes">
                <li>Double-check every answer. After you click Save SPR, you cannot edit this form.</li>
                <li>Keep your signature inside the box so it is not cut off.</li>
                <li>For more concerns, visit the Office of OASFA.</li>
            </ul>
        @endunless
    </div>
    @if(session('success'))
        <p class="spr-flash ok">{{ session('success') }} @if($application) Reference <strong>{{ $application->reference_no }}</strong>.@endif</p>
    @endif
    @if(session('warning'))
        <p class="spr-flash warn">{{ session('warning') }}</p>
    @endif
    @if(session('error'))
        <p class="spr-flash bad">{{ session('error') }}</p>
    @endif
    @if($errors->has('spr_duplicate'))
        <p class="spr-flash warn">{{ $errors->first('spr_duplicate') }}</p>
    @endif
    @if($errors->any())
        @php($fieldErrors = collect($errors->getMessages())->except('spr_duplicate')->flatten())
        @if($fieldErrors->isNotEmpty())
        <div class="spr-flash bad">
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
