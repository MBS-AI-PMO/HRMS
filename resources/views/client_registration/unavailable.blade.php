@extends('layout.auth')

@section('title', __('Registration unavailable'))

@section('brand_headline', __('Registration unavailable'))
@section('brand_tagline', __('Public client employee registration is not open at the moment.'))

@section('card_eyebrow', __('Access'))
@section('card_title', __('Registration unavailable'))
@section('card_subtitle')
    @if ($selectedClient ?? null)
        {{ __('Public registration is not enabled for') }}
        <strong>{{ trim(($selectedClient->company_name ?: '').' '.($selectedClient->first_name ?? '').' '.($selectedClient->last_name ?? '')) }}</strong>.
    @else
        {{ __('This registration link is invalid or registration is disabled.') }}
    @endif
@endsection

@section('content')
    <a href="{{ route('login') }}" class="auth-btn text-center d-flex align-items-center justify-content-center text-decoration-none">
        {{ __('Back to Login') }}
    </a>
@endsection
