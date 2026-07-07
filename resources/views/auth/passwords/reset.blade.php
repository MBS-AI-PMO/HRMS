@extends('layout.auth')

@section('title', __('Set New Password'))

@section('brand_headline', __('Create a new password'))
@section('brand_tagline', __('Choose a strong password to keep your HRMS account secure.'))

@section('card_eyebrow', __('Almost done'))
@section('card_title', __('Set new password'))
@section('card_subtitle', __('Enter your email and choose a new password for your account.'))

@section('content')
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="auth-field">
            <label for="email">{{ __('E-Mail Address') }}</label>
            <input id="email" type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus
                   placeholder="{{ __('you@company.com') }}">
            @error('email')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password">{{ __('Password') }}</label>
            <div class="auth-password-wrap">
                <input id="password" type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password" required autocomplete="new-password"
                       placeholder="{{ __('New password') }}">
                <button type="button" class="auth-password-toggle" data-target="#password"
                        aria-label="{{ __('Show password') }}">
                    <i class="fa fa-eye"></i>
                </button>
            </div>
            @error('password')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password-confirm">{{ __('Confirm Password') }}</label>
            <div class="auth-password-wrap">
                <input id="password-confirm" type="password" class="form-control"
                       name="password_confirmation" required autocomplete="new-password"
                       placeholder="{{ __('Confirm new password') }}">
                <button type="button" class="auth-password-toggle" data-target="#password-confirm"
                        aria-label="{{ __('Show password') }}">
                    <i class="fa fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="auth-btn">{{ __('Reset Password') }}</button>

        <div class="auth-links">
            <a href="{{ route('login') }}">&larr; {{ __('Back to login') }}</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function ($) {
    'use strict';
    $('.auth-password-toggle').on('click', function () {
        var $input = $($(this).data('target'));
        var $icon = $(this).find('i');
        var isHidden = $input.attr('type') === 'password';
        $input.attr('type', isHidden ? 'text' : 'password');
        $icon.toggleClass('fa-eye', !isHidden).toggleClass('fa-eye-slash', isHidden);
    });
})(jQuery);
</script>
@endpush
