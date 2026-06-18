@extends('layout.main')
@section('content')

    <section>
        <div class="container-fluid mb-3">
            <h4 class="mb-1">{{ __('My Team') }}</h4>
            <p class="text-muted mb-0">
                {{ __('View your team details. Leaders can edit the team; members can only view.') }}
            </p>
        </div>

        <div class="table-responsive">
            <table id="my-team-table" class="table">
                <thead>
                <tr>
                    <th>{{ __('Team Name') }}</th>
                    <th>{{ __('Your Role') }}</th>
                    <th>{{ __('Department Heads') }}</th>
                    <th>{{ __('Project Manager') }}</th>
                    <th>{{ __('Assistant HR') }}</th>
                    <th>{{ __('Department') }}</th>
                    <th>{{ __('Members') }}</th>
                    <th>{{ trans('file.Company') }}</th>
                    <th class="not-exported">{{ trans('file.action') }}</th>
                </tr>
                </thead>
            </table>
        </div>
    </section>

    @include('organization.team.partials.form_modal', ['allowCreate' => false])
    @include('organization.team.partials.view_modal')

@endsection

@php
    $singleCompanyId = \App\Support\CompanyScope::teamFormCompany()?->id
        ?? (\App\Support\CompanyScope::applies() && $companies->isNotEmpty() ? $companies->first()->id : null);
@endphp

@push('scripts')
<script type="text/javascript">
(function ($) {
    "use strict";

    $(document).ready(function () {
        $('#my-team-table').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: "{{ route('teams.my') }}",
            columns: [
                { data: 'team_name', name: 'team_name' },
                { data: 'your_role', name: 'your_role', orderable: false, searchable: false },
                { data: 'department_head', name: 'department_head' },
                { data: 'project_manager', name: 'project_manager' },
                { data: 'assistant_hr', name: 'assistant_hr' },
                { data: 'department', name: 'department' },
                { data: 'members', name: 'members', orderable: false },
                { data: 'company', name: 'company' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [],
            language: {
                lengthMenu: '_MENU_ {{ __("records per page") }}',
                info: '{{ __("Showing") }} _START_ - _END_ (_TOTAL_)',
                search: '{{ __("Search") }}',
                emptyTable: '{{ __("You are not assigned to any team yet.") }}',
                paginate: {
                    previous: '<i class="dripicons-chevron-left"></i>',
                    next: '<i class="dripicons-chevron-right"></i>'
                }
            }
        });
    });
})(jQuery);
</script>
@include('organization.team.partials.form_scripts', [
    'allowCreate' => false,
    'tableSelector' => '#my-team-table',
    'singleCompanyId' => $singleCompanyId,
])
@endpush
