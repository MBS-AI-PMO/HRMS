<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $general_setting->site_title ?? 'HRMS' }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.default.css') }}">
</head>
<body>
<div class="page login-page">
    <div class="container" style="max-width: 560px; margin: 3rem auto;">
        <div class="card shadow-sm text-center p-4">
            <h4>{{ __('Registration unavailable') }}</h4>
            <p class="text-muted mb-0">
                @if ($selectedCompany)
                    {{ __('Public registration is not enabled for') }} <strong>{{ $selectedCompany->company_name }}</strong>.
                @else
                    {{ __('This registration link is invalid or registration is disabled.') }}
                @endif
            </p>
            <a href="{{ route('login') }}" class="btn btn-primary mt-3">{{ __('Back to Login') }}</a>
        </div>
    </div>
</div>
</body>
</html>
