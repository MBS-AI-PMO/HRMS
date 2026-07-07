@extends('layout.auth')

@section('title', __('Sign in'))

@section('card_eyebrow', __('Welcome back'))
@section('card_title', __('Sign in to your account'))
@section('card_subtitle', __('Enter your credentials to access the HRMS dashboard.'))

@section('content')
    @include('shared.errors')
    @include('shared.flash_message')

    @if (session('error'))
        <div class="alert alert-danger auth-alert alert-dismissible text-center" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            {{ session('error') }}
        </div>
    @endif

    <div id="registration_success_alert" class="alert alert-success auth-alert" style="display:none;"></div>

    <form method="POST" action="{{ route('login') }}" id="login-form">
        @csrf

        <div class="auth-field">
            <label for="username">{{ __('Username') }}</label>
            <input id="username" type="text"
                   class="form-control @error('username') is-invalid @enderror"
                   name="username" value="{{ old('username') }}" required autofocus
                   placeholder="{{ __('Enter your username') }}">
            @error('username')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password">{{ __('Password') }}</label>
            <div class="auth-password-wrap">
                <input id="password" type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password" required autocomplete="current-password"
                       placeholder="{{ __('Enter your password') }}">
                <button type="button" class="auth-password-toggle" id="password-toggle"
                        aria-label="{{ __('Show password') }}" title="{{ __('Show password') }}">
                    <i class="fa fa-eye" aria-hidden="true"></i>
                </button>
            </div>
            @error('password')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <button type="submit" class="auth-btn">{{ __('Login') }}</button>

        <div class="auth-links auth-links--split">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
            @endif
            <a href="{{ route('employee.register') }}">{{ __('New employee? Register') }}</a>
        </div>
    </form>

    @if (! env('USER_VERIFIED'))
        <div class="auth-demo-btns">
            <button type="button" class="btn btn-success btn-sm admin-btn">{{ __('Admin') }}</button>
            <button type="button" class="btn btn-info btn-sm staff-btn">{{ __('Staff') }}</button>
            <button type="button" class="btn btn-warning btn-sm client-btn">{{ __('Client') }}</button>
        </div>
        <p class="auth-demo-note">{{ __('For attendance device related features, purchase the attendance device addon.') }}</p>
    @endif
@endsection

@push('scripts')
<script>
(function ($) {
    'use strict';

    $('.admin-btn').on('click', function () {
        $("input[name='username']").val('admin');
        $("input[name='password']").val('admin');
    });

    $('.staff-btn').on('click', function () {
        $("input[name='username']").val('staff');
        $("input[name='password']").val('staff');
    });

    $('.client-btn').on('click', function () {
        $("input[name='username']").val('client');
        $("input[name='password']").val('client');
    });

    $('#password-toggle').on('click', function () {
        var $password = $('#password');
        var $icon = $(this).find('i');
        var isHidden = $password.attr('type') === 'password';

        $password.attr('type', isHidden ? 'text' : 'password');
        $icon.toggleClass('fa-eye', !isHidden).toggleClass('fa-eye-slash', isHidden);
        $(this).attr({
            'aria-label': isHidden ? @json(__('Hide password')) : @json(__('Show password')),
            'title': isHidden ? @json(__('Hide password')) : @json(__('Show password'))
        });
    });

    try {
        var regMsg = sessionStorage.getItem('registration_success');
        if (regMsg) {
            sessionStorage.removeItem('registration_success');
            $('#registration_success_alert').text(regMsg).show();
        }
    } catch (e) {}
})(jQuery);
</script>
@endpush
