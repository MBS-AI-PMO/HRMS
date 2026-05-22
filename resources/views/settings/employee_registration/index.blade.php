@extends('layout.main')
@section('content')
    <section class="forms">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">{{ __('Employee Registration Settings') }}</h4>
                    <a href="{{ route('employee.register') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        {{ __('Public Registration URL') }}
                    </a>
                </div>
                <div class="card-body">
                    @if (!empty($migrationRequired))
                        <div class="alert alert-danger">
                            <strong>{{ __('Database table missing') }}</strong><br>
                            {{ __('Run this command in project folder, then refresh:') }}
                            <code>php artisan migrate</code>
                        </div>
                    @else
                        <p class="text-muted">{{ __('List of registration settings per company. Click Edit to configure or update.') }}</p>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="registration_settings_table">
                                <thead>
                                    <tr>
                                        <th>{{ trans('file.Company') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Page Title') }}</th>
                                        <th>{{ __('Auto Approve') }}</th>
                                        <th>{{ __('Attendance Type') }}</th>
                                        <th>{{ __('Registration URL') }}</th>
                                        <th>{{ __('Last Updated') }}</th>
                                        <th class="not-exported">{{ trans('file.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td><strong>{{ $row->company_name }}</strong></td>
                                            <td>
                                                @if ($row->is_enabled)
                                                    <span class="badge badge-success">{{ __('Enabled') }}</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $row->page_title ?: '—' }}</td>
                                            <td>
                                                @if ($row->auto_approve)
                                                    <span class="badge badge-info">{{ __('Yes') }}</span>
                                                @else
                                                    {{ __('No') }}
                                                @endif
                                            </td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $row->default_attendance_type ?? 'location_based')) }}</td>
                                            <td>
                                                @if ($row->is_enabled)
                                                    <a href="{{ $row->registration_url }}" target="_blank" class="small">{{ $row->registration_url }}</a>
                                                @else
                                                    <span class="text-muted small">{{ __('Enable registration to get URL') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $row->updated_at ? $row->updated_at->format(env('Date_Format', 'Y-m-d') . ' H:i') : '—' }}</td>
                                            <td>
                                                <a href="{{ route('employee_registration_settings.edit', $row->company_id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    <i class="dripicons-pencil"></i> {{ __('Edit') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">{{ __('No company found.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
@if (empty($migrationRequired) && $rows->isNotEmpty())
<script type="text/javascript">
    $(document).ready(function () {
        $('#registration_settings_table').DataTable({
            order: [[0, 'asc']],
            pageLength: 25,
        });
    });
</script>
@endif
@endpush
