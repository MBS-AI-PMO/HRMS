@extends('layout.main')
@section('content')

    <section>
        <div class="container-fluid"><span id="general_result"></span></div>

        <div class="container-fluid mb-3">
            @can('store-team')
                <button type="button" class="btn btn-info" id="create_record"><i class="fa fa-plus"></i> {{ __('Add Team') }}</button>
            @endcan
            @can('delete-team')
                <button type="button" class="btn btn-danger" id="bulk_delete"><i class="fa fa-minus-circle"></i> {{ __('Bulk delete') }}</button>
            @endcan
        </div>

        <div class="table-responsive">
            <table id="team-table" class="table">
                <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{ __('Team Name') }}</th>
                    <th>{{ __('Department Head') }}</th>
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

    @include('organization.team.partials.form_modal', ['allowCreate' => true])

    <div id="confirmModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">{{ trans('file.Confirmation') }}</h2>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <h4 align="center">{{ __('Are you sure you want to remove this data?') }}</h4>
                </div>
                <div class="modal-footer">
                    <button type="button" id="ok_button" class="btn btn-danger">{{ trans('file.OK') }}</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('file.Cancel') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@php
    $singleCompanyId = \App\Support\CompanyScope::applies() && $companies->count() === 1
        ? $companies->first()->id
        : null;
@endphp

@push('scripts')
<script type="text/javascript">
(function ($) {
    "use strict";

    let deleteId = null;

    $(document).ready(function () {
        const table = $('#team-table').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: "{{ route('teams.index') }}",
            columns: [
                { data: 'id', orderable: false, searchable: false },
                { data: 'team_name', name: 'team_name' },
                { data: 'department_head', name: 'department_head' },
                { data: 'project_manager', name: 'project_manager' },
                { data: 'assistant_hr', name: 'assistant_hr' },
                { data: 'department', name: 'department' },
                { data: 'members', name: 'members', orderable: false },
                { data: 'company', name: 'company' },
                { data: 'action', name: 'action', orderable: false }
            ],
            order: [],
            language: {
                lengthMenu: '_MENU_ {{ __("records per page") }}',
                info: '{{ __("Showing") }} _START_ - _END_ (_TOTAL_)',
                search: '{{ __("Search") }}',
                paginate: { previous: '<i class="dripicons-chevron-left"></i>', next: '<i class="dripicons-chevron-right"></i>' }
            },
            columnDefs: [{
                orderable: false,
                targets: 0,
                render: function (data) {
                    return '<input type="checkbox" name="team_checkbox" value="' + data + '"><label></label>';
                }
            }]
        });

        $('#create_record').on('click', function () {
            $('#sample_form')[0].reset();
            $('#member_ids').selectpicker('refresh');
            $('.selectpicker').selectpicker('refresh');
            $('#hidden_id').val('');
            $('#action').val('{{ trans('file.Add') }}');
            $('#action_button').val('{{ trans('file.Add') }}');
            $('#exampleModalLabel').text('{{ __('Add Team') }}');
            $('#formModal').modal('show');
            if ($('#company_id').is('select') && $('#company_id option').length === 1) {
                $('#company_id').val($('#company_id option:first').val()).selectpicker('refresh');
            }
            $('#company_id').trigger('change');
        });

        $(document).on('click', '.delete', function () {
            deleteId = $(this).attr('id');
            $('#confirmModal').modal('show');
        });

        $('#ok_button').on('click', function () {
            $.ajax({
                url: "{{ url('organization/teams') }}/" + deleteId + "/delete",
                success: function (data) {
                    $('#confirmModal').modal('hide');
                    table.ajax.reload();
                    if (data.success) {
                        $('#general_result').html('<div class="alert alert-success">' + data.success + '</div>');
                    }
                }
            });
        });

        $('#bulk_delete').on('click', function () {
            const ids = [];
            $('input[name="team_checkbox"]:checked').each(function () {
                ids.push($(this).val());
            });

            if (ids.length === 0) {
                alert('{{ __('Please select at least one team.') }}');
                return;
            }

            $.ajax({
                url: "{{ route('mass_delete_teams') }}",
                method: "POST",
                data: {
                    teamIdArray: ids,
                    _token: "{{ csrf_token() }}"
                },
                success: function (data) {
                    table.ajax.reload();
                    if (data.success) {
                        $('#general_result').html('<div class="alert alert-success">' + data.success + '</div>');
                    }
                }
            });
        });
    });
})(jQuery);
</script>
@include('organization.team.partials.form_scripts', [
    'allowCreate' => true,
    'tableSelector' => '#team-table',
    'singleCompanyId' => $singleCompanyId,
])
@endpush
