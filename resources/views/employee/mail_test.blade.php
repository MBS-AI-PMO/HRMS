@extends('layout.main')
@section('content')

<section>
    @include('shared.errors')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">{{ __('Employee email test') }}</h3>
                        <a href="{{ route('employee.EmployeeDashboard') }}" class="btn btn-sm btn-outline-secondary">
                            {{ __('Back to dashboard') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>{{ __('If log shows [MAIL AFTER] but inbox is empty') }}</strong><br>
                            {{ __('SMTP accepted the email — the problem is delivery to Gmail/Yahoo, not HRMS code.') }}<br>
                            {{ __('Fix SPF + DKIM in cPanel → Email Deliverability for your domain, or test from Webmail to the same Gmail address.') }}
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <p class="text-muted mb-4">
                            {{ __('Use this page to verify that SMTP can deliver email to employee-role accounts using the same address resolution as leave/WFH notifications.') }}
                        </p>

                        <table class="table table-bordered table-sm">
                            <tbody>
                                <tr>
                                    <th style="width:35%">{{ __('Employee') }}</th>
                                    <td>{{ $diagnostics['employee_name'] ?? '—' }} (ID: {{ $diagnostics['user_id'] }})</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Role') }}</th>
                                    <td>{{ $diagnostics['role_name'] }} (role_users_id: {{ $diagnostics['role_users_id'] }})</td>
                                </tr>
                                <tr>
                                    <th>users.email</th>
                                    <td>{{ $diagnostics['user_email'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>employees.email</th>
                                    <td>{{ $diagnostics['employee_record_email'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Resolved send address') }}</th>
                                    <td>
                                        @if (!empty($diagnostics['resolved_email']))
                                            <span class="text-success font-weight-bold">{{ $diagnostics['resolved_email'] }}</span>
                                            <small class="text-muted">({{ __('source') }}: {{ $diagnostics['email_source'] }})</small>
                                        @else
                                            <span class="text-danger">{{ __('No valid email — update profile or ask HR') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('SMTP') }}</th>
                                    <td>
                                        {{ $diagnostics['mail_host'] }}:{{ $diagnostics['mail_port'] ?? '—' }}
                                        ({{ $diagnostics['mail_encryption'] ?? '—' }})
                                        / {{ __('from') }} {{ $diagnostics['mail_from'] }}
                                    </td>
                                </tr>
                                @if (!empty($diagnostics['delivery_hint']))
                                    <tr>
                                        <th>{{ __('Delivery note') }}</th>
                                        <td class="text-warning">{{ $diagnostics['delivery_hint'] }}</td>
                                    </tr>
                                @endif
                                @if (!empty($diagnostics['typo_hint']))
                                    <tr>
                                        <th>{{ __('Warning') }}</th>
                                        <td class="text-warning">{{ $diagnostics['typo_hint'] }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                        @if ($isAdmin)
                            <div class="alert alert-info">
                                {{ __('Admin: append ?user_id=EMPLOYEE_ID to test another employee account.') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('employee.mail-test.send', request()->only('user_id')) }}" class="mt-3">
                            @csrf
                            @if (request('user_id'))
                                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                            @endif
                            <button type="submit" class="btn btn-primary" @if(empty($diagnostics['resolved_email'])) disabled @endif>
                                <i class="fa fa-envelope"></i> {{ __('Send test email now') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
