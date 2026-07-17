@extends('layout.main')
@section('content')

    <section>
        <div class="container-fluid mb-3">
            <h4 class="mb-1">{{ __('My Team') }}</h4>
            <p class="text-muted mb-0">
                {{ __('Projects you lead and the employees assigned to them. Manage their leave/WFH requests from the L/ WFH Requests menu.') }}
            </p>
        </div>

        <div class="table-responsive">
            <table id="my-team-table" class="table">
                <thead>
                <tr>
                    <th>{{ __('Project') }}</th>
                    <th>{{ trans('file.Client') }}</th>
                    <th>{{ __('Members') }}</th>
                    <th>{{ __('Members Count') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="not-exported">{{ trans('file.action') }}</th>
                </tr>
                </thead>
            </table>
        </div>
    </section>

    <div class="modal fade" id="teamMembersModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="teamMembersModalLabel">{{ __('Team Members') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ trans('file.Department') }}</th>
                            </tr>
                            </thead>
                            <tbody id="team-members-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

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
                { data: 'title', name: 'title' },
                { data: 'client', name: 'client', orderable: false, searchable: false },
                { data: 'members', name: 'members', orderable: false, searchable: false },
                { data: 'member_count', name: 'member_count', orderable: false, searchable: false },
                { data: 'status', name: 'project_status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [],
            language: {
                lengthMenu: '_MENU_ {{ __("records per page") }}',
                info: '{{ __("Showing") }} _START_ - _END_ (_TOTAL_)',
                search: '{{ __("Search") }}',
                emptyTable: '{{ __("You are not assigned as a project lead yet.") }}',
                paginate: {
                    previous: '<i class="dripicons-chevron-left"></i>',
                    next: '<i class="dripicons-chevron-right"></i>'
                }
            }
        });

        $('#my-team-table').on('click', '.view-members', function () {
            var projectId = $(this).data('id');
            var title = $(this).data('title') || '{{ __('Team Members') }}';
            var membersUrl = "{{ url('organization/teams/my') }}/" + projectId + "/members";

            $('#teamMembersModalLabel').text(title);
            $('#team-members-body').html('<tr><td colspan="4" class="text-center">{{ __('Loading...') }}</td></tr>');
            $('#teamMembersModal').modal('show');

            $.get(membersUrl, function (response) {
                var rows = '';

                if (response.members && response.members.length) {
                    $.each(response.members, function (index, member) {
                        rows += '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + (member.name || '-') + '</td>' +
                            '<td>' + (member.email || '-') + '</td>' +
                            '<td>' + (member.department || '-') + '</td>' +
                            '</tr>';
                    });
                } else {
                    rows = '<tr><td colspan="4" class="text-center">{{ __('No members assigned to this project yet.') }}</td></tr>';
                }

                $('#team-members-body').html(rows);
            }).fail(function () {
                $('#team-members-body').html('<tr><td colspan="4" class="text-center text-danger">{{ __('Unable to load members.') }}</td></tr>');
            });
        });
    });
})(jQuery);
</script>
@endpush
