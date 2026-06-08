<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ __('Your EMS account has been registered') }}</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <p>{{ __('Hello') }} {{ $employeeName }},</p>

    <p>{{ __('Your email address has been registered successfully in our EMS.') }}</p>

    <p>
        <strong>{{ __('Registered email') }}:</strong>
        {{ $registeredEmail }}
    </p>

    <p>{{ __('Your account details are as follows:') }}</p>

    <ul>
        <li>
            <strong>{{ __('Company') }}:</strong>
            {{ $companyName }}
        </li>

        <li>
            <strong>{{ __('Department') }}:</strong>
            {{ $departmentName }}
        </li>

        <li>
            <strong>{{ __('Designation') }}:</strong>
            {{ $designationName }}
        </li>

        @if (!empty($officeShiftName) && $officeShiftName !== '—')
            <li>
                <strong>{{ __('Shift') }}:</strong>
                {{ $officeShiftName }}
            </li>
        @endif
    </ul>

    <p>{{ __('Please use the login credentials below:') }}</p>

    <ul>
        <li>
            <strong>{{ __('Staff Id') }}:</strong>
            {{ $staffId }}
        </li>

        <li>
            <strong>{{ __('Username') }}:</strong>
            {{ $username }}
        </li>

        <li>
            <strong>{{ __('Password') }}:</strong>
            {{ $password }}
        </li>
    </ul>

    <!-- Login Button -->
    <p>
        <a href="{{ $loginUrl }}"
            style="display:inline-block;padding:10px 18px;background:#6c5ce7;color:#ffffff;text-decoration:none;border-radius:6px;">
            {{ __('Login') }}
        </a>
    </p>

    <!-- Android App Download Button -->
    <p>
        <a href="{{ url('app_link/app-release.apk') }}"
            style="display:inline-block;padding:10px 18px;background:#28a745;color:#ffffff;text-decoration:none;border-radius:6px;">
            {{ __('Download Android App') }}
        </a>
    </p>

    <p>
        {{ __('If you are using a mobile device, you can install the EMS Android application using the button above.') }}
    </p>

    <p style="font-size:12px;color:#666;">
        {{ __('Please change your password after first login if your organization requires it.') }}
    </p>

</body>

</html>
