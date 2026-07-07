@extends('layout.auth')

@section('title', __('Reset Password'))

@section('brand_headline', __('Forgot your password?'))
@section('brand_tagline', __('No worries — we will send you a secure link to reset your password and get back into your account.'))

@section('card_eyebrow', __('Account recovery'))
@section('card_title', __('Reset password'))
@section('card_subtitle', __('Enter the email address linked to your account and we will email you a reset link.'))

@section('content')
    @if (session('status'))
        <div class="alert alert-success auth-alert" role="alert">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="auth-field">
            <label for="email">{{ __('E-Mail Address') }}</label>
            <input id="email" type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                   placeholder="{{ __('you@company.com') }}">
            @error('email')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <button type="submit" class="auth-btn">{{ __('Send Password Reset Link') }}</button>

        <div class="auth-links">
            <a href="{{ route('login') }}">&larr; {{ __('Back to login') }}</a>
        </div>
    </form>
@endsection
