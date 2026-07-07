@extends('layout.auth')

@section('title', __('Registration unavailable'))

@section('brand_headline', __('Registration unavailable'))
@section('brand_tagline', __('Public employee registration is not open for this organization at the moment.'))

@section('card_eyebrow', __('Access'))
@section('card_title', __('Registration unavailable'))
@section('card_subtitle')
    @if ($selectedCompany ?? null)
        {{ __('Public registration is not enabled for') }} <strong>{{ $selectedCompany->company_name }}</strong>.
    @else
        {{ __('This registration link is invalid or registration is disabled.') }}
    @endif
@endsection

@section('content')
    <a href="{{ route('login') }}" class="auth-btn text-center d-flex align-items-center justify-content-center text-decoration-none">
        {{ __('Back to Login') }}
    </a>
@endsection
