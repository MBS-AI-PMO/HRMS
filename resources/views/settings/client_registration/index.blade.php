@extends('layout.main')
@section('content')
    <section class="forms">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">{{ __('Client Employee Registration') }}</h4>
                    @if (empty($migrationRequired))
                        <a href="{{ route('client_registration_settings.create') }}" class="btn btn-primary btn-sm">
                            <i class="dripicons-plus"></i> {{ __('Create Registration Link') }}
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if (!empty($migrationRequired))
                        <div class="alert alert-danger">
                            <strong>{{ __('Database table missing') }}</strong><br>
                            {{ __('Run this command in project folder, then refresh:') }}
                            <code>php artisan migrate</code>
                        </div>
                    @else
                        <p class="text-muted">{{ __('Create registration links per client and project. New employees who register will be assigned to the selected project(s) automatically.') }}</p>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="client_registration_settings_table">
                                <thead>
                                    <tr>
                                        <th>{{ trans('file.Client') }}</th>
                                        <th>{{ __('Label') }}</th>
                                        <th>{{ trans('file.Projects') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Registration URL') }}</th>
                                        <th>{{ __('Last Updated') }}</th>
                                        <th class="not-exported">{{ trans('file.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td><strong>{{ $row->client_name }}</strong></td>
                                            <td>{{ $row->label ?: '—' }}</td>
                                            <td>
                                                @if ($row->project_names->isNotEmpty())
                                                    {{ $row->project_names->implode(', ') }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($row->is_enabled)
                                                    <span class="badge badge-success">{{ __('Enabled') }}</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($row->is_enabled)
                                                    <a href="{{ $row->registration_url }}" target="_blank" class="small">{{ $row->registration_url }}</a>
                                                @else
                                                    <span class="text-muted small">{{ __('Enable registration to get URL') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $row->updated_at ? $row->updated_at->format(config('variable.date_format', 'd-m-Y') . ' H:i') : '—' }}</td>
                                            <td>
                                                <a href="{{ route('client_registration_settings.edit', $row->id) }}"
                                                   class="btn btn-primary btn-sm">
                                                    <i class="dripicons-pencil"></i> {{ __('Edit') }}
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm btn-delete-setting" data-id="{{ $row->id }}">
                                                    <i class="dripicons-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">{{ __('No registration links yet. Click Create Registration Link to add one.') }}</td>
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
        $('#client_registration_settings_table').DataTable({
            order: [[5, 'desc']],
            pageLength: 25,
        });

        $(document).on('click', '.btn-delete-setting', function () {
            if (!confirm(@json(__('Are you sure you want to delete this registration link?')))) {
                return;
            }
            const id = $(this).data('id');
            $.ajax({
                url: @json(route('client_registration_settings.destroy', ['id' => '__ID__'])).replace('__ID__', id),
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function () {
                    location.reload();
                },
                error: function (xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.error) || @json(__('Delete failed')));
                }
            });
        });
    });
</script>
@endif
@endpush
