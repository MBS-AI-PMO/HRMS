<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Your HRMS account credentials') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>{{ __('Hello') }} {{ $employeeName }},</p>
    <p>{{ __('Your employee account has been created. Please use the credentials below to login:') }}</p>
    <ul>
        <li><strong>{{ __('Staff Id') }}:</strong> {{ $staffId }}</li>
        <li><strong>{{ __('Username') }}:</strong> {{ $username }}</li>
        <li><strong>{{ __('Password') }}:</strong> {{ $password }}</li>
    </ul>
    <p>
        <a href="{{ $loginUrl }}" style="display:inline-block;padding:10px 18px;background:#6c5ce7;color:#fff;text-decoration:none;border-radius:6px;">
            {{ __('Login') }}
        </a>
    </p>
    <p style="font-size:12px;color:#666;">{{ __('Please change your password after first login if your organization requires it.') }}</p>
</body>
</html>
