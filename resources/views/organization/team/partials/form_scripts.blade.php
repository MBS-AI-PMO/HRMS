<script type="text/javascript">
(function ($) {
    "use strict";

    const allowCreate = {{ $allowCreate ? 'true' : 'false' }};
    const tableSelector = '{{ $tableSelector }}';
    const defaultCompanyId = @json($singleCompanyId ?? null);

    function resolveCompanyId() {
        let companyId = $('#company_id').val();
        if (!companyId && defaultCompanyId) {
            companyId = String(defaultCompanyId);
            $('#company_id').val(companyId);
        }
        return companyId;
    }

    function fillSelect($select, items, selectedValue, placeholder) {
        $select.empty();
        $select.append('<option value="">' + placeholder + '</option>');
        items.forEach(function (item) {
            $select.append('<option value="' + item.id + '">' + item.name + '</option>');
        });
        $select.selectpicker('refresh');
        $select.selectpicker('val', selectedValue ? String(selectedValue) : '');
    }

    function fillDepartmentSelect(departments, selectedValue) {
        const $select = $('#department_id');
        $select.empty();
        $select.append('<option value="">{{ __('Select Department') }}</option>');
        departments.forEach(function (item) {
            $select.append('<option value="' + item.id + '">' + item.department_name + '</option>');
        });
        $select.selectpicker('refresh');
        $select.selectpicker('val', selectedValue ? String(selectedValue) : '');
    }

    function fillDepartmentHeadsSelect(employees, selectedIds) {
        const $select = $('#department_head_ids');
        $select.empty();
        employees.forEach(function (item) {
            $select.append('<option value="' + item.id + '">' + item.name + '</option>');
        });
        $select.selectpicker('refresh');
        $select.selectpicker('val', selectedIds && selectedIds.length ? selectedIds.map(String) : []);
    }

    function fillMembersSelect(employees, selectedIds) {
        const $select = $('#member_ids');
        $select.empty();
        employees.forEach(function (item) {
            $select.append('<option value="' + item.id + '">' + item.name + '</option>');
        });
        $select.selectpicker('refresh');
        $select.selectpicker('val', selectedIds && selectedIds.length ? selectedIds.map(String) : []);
    }

    function clearDynamicTeamSelects() {
        ['#department_id', '#project_manager_id', '#assistant_hr_id', '#department_head_ids', '#member_ids'].forEach(function (selector) {
            const $select = $(selector);
            $select.empty();
            $select.selectpicker('refresh');
            $select.selectpicker('val', $select.prop('multiple') ? [] : '');
        });
    }

    window.resetTeamFormForCreate = function () {
        $('#form_result').html('');
        $('#sample_form')[0].reset();
        $('#hidden_id').val('');
        $('#team_name').val('');
        $('#description').val('');
        $('#action').val('{{ trans('file.Add') }}');
        $('#action_button').val('{{ trans('file.Add') }}');
        $('#exampleModalLabel').text('{{ __('Add Team') }}');
        clearDynamicTeamSelects();

        if (defaultCompanyId) {
            $('#company_id').val(defaultCompanyId);
            if ($('#company_id').is('select')) {
                $('#company_id').selectpicker('refresh');
            }
        } else if ($('#company_id').is('select')) {
            $('#company_id').val('').selectpicker('refresh');
        }
    };

    function loadCompanyOptions(selectedPm, selectedAssistant, selectedMembers, selectedDepartment, selectedDeptHeads) {
        const companyId = resolveCompanyId();
        if (!companyId) {
            return;
        }

        $.get("{{ route('teams.employees_options') }}", { company_id: companyId }, function (res) {
            fillDepartmentHeadsSelect(res.employees || [], selectedDeptHeads);
            fillSelect($('#project_manager_id'), res.employees || [], selectedPm, '{{ __('Select Project Manager') }}');
            fillSelect($('#assistant_hr_id'), res.employees || [], selectedAssistant, '{{ __('Select Assistant HR') }}');
            fillDepartmentSelect(res.departments || [], selectedDepartment);
            fillMembersSelect(res.employees || [], selectedMembers);
        });
    }

    window.loadTeamFormCompanyOptions = function (selectedPm, selectedAssistant, selectedMembers, selectedDepartment, selectedDeptHeads) {
        loadCompanyOptions(selectedPm, selectedAssistant, selectedMembers, selectedDepartment, selectedDeptHeads);
    };

    window.openTeamEditModal = function (id) {
        $('#form_result').html('');
        $.ajax({
            url: "{{ url('organization/teams') }}/" + id + "/edit",
            dataType: "json",
            success: function (data) {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                $('#team_name').val(data.data.team_name);
                if ($('#company_id').is('select')) {
                    $('#company_id').val(data.data.company_id).selectpicker('refresh');
                } else {
                    $('#company_id').val(data.data.company_id);
                }
                $('#description').val(data.data.description);
                $('#hidden_id').val(data.data.id);
                $('#action').val('{{ __('Edit') }}');
                $('#action_button').val('{{ __('Edit') }}');
                $('#exampleModalLabel').text('{{ __('Edit Team') }}');
                $('#formModal').one('shown.bs.modal', function () {
                    loadCompanyOptions(
                        data.data.project_manager_id,
                        data.data.assistant_hr_id,
                        data.member_ids,
                        data.data.department_id,
                        data.department_head_ids
                    );
                });
                $('#formModal').modal('show');
            }
        });
    };

    $(document).ready(function () {
        if (defaultCompanyId) {
            $('#company_id').val(defaultCompanyId);
        }

        $('#company_id').on('change', function () {
            loadCompanyOptions();
        });

        $('#formModal').on('hidden.bs.modal', function () {
            if (!$('#hidden_id').val()) {
                clearDynamicTeamSelects();
            }
        });

        $('#formModal').on('shown.bs.modal', function () {
            if ($('#hidden_id').val()) {
                return;
            }
            if (resolveCompanyId()) {
                loadCompanyOptions();
            } else {
                clearDynamicTeamSelects();
            }
        });

        $('#sample_form').on('submit', function (event) {
            event.preventDefault();
            if (defaultCompanyId) {
                $('#company_id').val(defaultCompanyId);
            }
            const isCreate = $('#action').val() === '{{ trans('file.Add') }}';
            const actionUrl = isCreate ? "{{ route('teams.store') }}" : "{{ route('teams.update') }}";

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
                        $(tableSelector).DataTable().ajax.reload();
                        setTimeout(function () {
                            $('#formModal').modal('hide');
                            $('#form_result').html('');
                        }, 1200);
                    }
                    if (data.error) {
                        $('#form_result').html('<div class="alert alert-danger">' + data.error + '</div>');
                    }
                }
            });
        });

        $(document).on('click', '.edit-team', function () {
            window.openTeamEditModal($(this).data('id'));
        });

        $(document).on('click', '.view-team', function () {
            const id = $(this).data('id');
            $.ajax({
                url: "{{ url('organization/teams') }}/" + id + "/show",
                dataType: "json",
                success: function (response) {
                    if (response.error) {
                        alert(response.error);
                        return;
                    }
                    const data = response.data;
                    $('#view_team_name').text(data.team_name || '-');
                    $('#view_team_company').text(data.company || '-');
                    $('#view_team_department').text(data.department || '-');
                    $('#view_team_department_heads').text(data.department_heads || '-');
                    $('#view_team_project_manager').text(data.project_manager || '-');
                    $('#view_team_assistant_hr').text(data.assistant_hr || '-');
                    $('#view_team_members').text(data.members || '-');
                    $('#view_team_description').text(data.description || '-');
                    $('#viewTeamModal').modal('show');
                }
            });
        });
    });
})(jQuery);
</script>
