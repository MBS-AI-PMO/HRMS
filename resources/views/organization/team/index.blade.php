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

    <div id="formModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ __('Add Team') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><i class="dripicons-cross"></i></button>
                </div>
                <div class="modal-body">
                    <span id="form_result"></span>
                    <form method="post" id="sample_form" class="form-horizontal">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>{{ __('Team Name') }} *</label>
                                <input type="text" name="team_name" id="team_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ trans('file.Company') }} *</label>
                                <select name="company_id" id="company_id" class="form-control selectpicker"
                                        data-live-search="true" title="{{ __('Select Company') }}" required>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ trans('file.Department') }}</label>
                                <select name="department_id" id="department_id" class="form-control selectpicker"
                                        data-live-search="true" title="{{ __('Select Department') }}">
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Project Manager') }} *</label>
                                <select name="project_manager_id" id="project_manager_id" class="form-control selectpicker"
                                        data-live-search="true" title="{{ __('Select Project Manager') }}" required>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Assistant HR') }}</label>
                                <select name="assistant_hr_id" id="assistant_hr_id" class="form-control selectpicker"
                                        data-live-search="true" title="{{ __('Select Assistant HR') }}">
                                </select>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>{{ __('Team Members') }}</label>
                                <select name="member_ids[]" id="member_ids" class="form-control selectpicker"
                                        data-live-search="true" multiple title="{{ __('Select Team Members') }}">
                                </select>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-12 form-group text-center">
                                <input type="hidden" name="action" id="action">
                                <input type="hidden" name="hidden_id" id="hidden_id">
                                <input type="submit" id="action_button" class="btn btn-warning" value="{{ trans('file.Add') }}">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

@push('scripts')
<script type="text/javascript">
(function ($) {
    "use strict";

    let deleteId = null;

    function fillSelect($select, items, selectedValue, placeholder) {
        $select.empty();
        $select.append('<option value="">' + placeholder + '</option>');
        items.forEach(function (item) {
            const selected = String(selectedValue) === String(item.id) ? 'selected' : '';
            $select.append('<option value="' + item.id + '" ' + selected + '>' + item.name + '</option>');
        });
        $select.selectpicker('refresh');
    }

    function fillDepartmentSelect(departments, selectedValue) {
        const $select = $('#department_id');
        $select.empty();
        $select.append('<option value="">{{ __('Select Department') }}</option>');
        departments.forEach(function (item) {
            const selected = String(selectedValue) === String(item.id) ? 'selected' : '';
            $select.append('<option value="' + item.id + '" ' + selected + '>' + item.department_name + '</option>');
        });
        $select.selectpicker('refresh');
    }

    function fillMembersSelect(employees, selectedIds) {
        const $select = $('#member_ids');
        $select.empty();
        employees.forEach(function (item) {
            const selected = (selectedIds || []).map(String).includes(String(item.id)) ? 'selected' : '';
            $select.append('<option value="' + item.id + '" ' + selected + '>' + item.name + '</option>');
        });
        $select.selectpicker('refresh');
    }

    function loadCompanyOptions(selectedPm, selectedAssistant, selectedMembers, selectedDepartment) {
        const companyId = $('#company_id').val();
        if (!companyId) {
            return;
        }

        $.get("{{ route('teams.employees_options') }}", { company_id: companyId }, function (res) {
            fillSelect($('#project_manager_id'), res.employees, selectedPm, '{{ __('Select Project Manager') }}');
            fillSelect($('#assistant_hr_id'), res.employees, selectedAssistant, '{{ __('Select Assistant HR') }}');
            fillDepartmentSelect(res.departments, selectedDepartment);
            fillMembersSelect(res.employees, selectedMembers);
        });
    }

    $(document).ready(function () {
        const table = $('#team-table').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: "{{ route('teams.index') }}",
            columns: [
                { data: 'id', orderable: false, searchable: false },
                { data: 'team_name', name: 'team_name' },
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
            if ($('#company_id option').length === 1) {
                $('#company_id').val($('#company_id option:first').val()).selectpicker('refresh');
            }
            loadCompanyOptions();
        });

        $('#company_id').on('change', function () {
            loadCompanyOptions();
        });

        $('#sample_form').on('submit', function (event) {
            event.preventDefault();
            const actionUrl = $('#action').val() === '{{ trans('file.Add') }}'
                ? "{{ route('teams.store') }}"
                : "{{ route('teams.update') }}";

            $.ajax({
                url: actionUrl,
                method: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function (data) {
                    if (data.errors) {
                        let html = '<div class="alert alert-danger">';
                        data.errors.forEach(function (error) {
                            html += '<div>' + error + '</div>';
                        });
                        html += '</div>';
                        $('#form_result').html(html);
                    }

                    if (data.success) {
                        $('#form_result').html('<div class="alert alert-success">' + data.success + '</div>');
                        $('#sample_form')[0].reset();
                        $('.selectpicker').selectpicker('refresh');
                        table.ajax.reload();
                        setTimeout(function () {
                            $('#formModal').modal('hide');
                            $('#form_result').html('');
                        }, 1200);
                    }
                }
            });
        });

        $(document).on('click', '.edit', function () {
            const id = $(this).attr('id');
            $('#form_result').html('');
            $.ajax({
                url: "{{ url('organization/teams') }}/" + id + "/edit",
                dataType: "json",
                success: function (data) {
                    $('#team_name').val(data.data.team_name);
                    $('#company_id').val(data.data.company_id).selectpicker('refresh');
                    $('#description').val(data.data.description);
                    $('#hidden_id').val(data.data.id);
                    $('#action').val('{{ __('Edit') }}');
                    $('#action_button').val('{{ __('Edit') }}');
                    $('#exampleModalLabel').text('{{ __('Edit Team') }}');
                    loadCompanyOptions(
                        data.data.project_manager_id,
                        data.data.assistant_hr_id,
                        data.member_ids,
                        data.data.department_id
                    );
                    $('#formModal').modal('show');
                }
            });
        });

        let deleteId = null;
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
@endpush
