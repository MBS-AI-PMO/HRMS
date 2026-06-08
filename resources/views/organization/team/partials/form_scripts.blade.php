<script type="text/javascript">
(function ($) {
    "use strict";

    const allowCreate = {{ $allowCreate ? 'true' : 'false' }};
    const tableSelector = '{{ $tableSelector }}';
    const defaultCompanyId = @json($singleCompanyId ?? null);

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

    function loadCompanyOptions(selectedPm, selectedAssistant, selectedMembers, selectedDepartment, selectedDeptHead) {
        let companyId = $('#company_id').val();
        if (!companyId && defaultCompanyId) {
            companyId = defaultCompanyId;
        }
        if (!companyId) {
            return;
        }

        $.get("{{ route('teams.employees_options') }}", { company_id: companyId }, function (res) {
            fillSelect($('#department_head_id'), res.employees, selectedDeptHead, '{{ __('Select Department Head') }}');
            fillSelect($('#project_manager_id'), res.employees, selectedPm, '{{ __('Select Project Manager') }}');
            fillSelect($('#assistant_hr_id'), res.employees, selectedAssistant, '{{ __('Select Assistant HR') }}');
            fillDepartmentSelect(res.departments, selectedDepartment);
            fillMembersSelect(res.employees, selectedMembers);
        });
    }

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
                }
                $('#description').val(data.data.description);
                $('#hidden_id').val(data.data.id);
                $('#action').val('{{ __('Edit') }}');
                $('#action_button').val('{{ __('Edit') }}');
                $('#exampleModalLabel').text('{{ __('Edit Team') }}');
                loadCompanyOptions(
                    data.data.project_manager_id,
                    data.data.assistant_hr_id,
                    data.member_ids,
                    data.data.department_id,
                    data.data.department_head_id
                );
                $('#formModal').modal('show');
            }
        });
    };

    $(document).ready(function () {
        if (defaultCompanyId && allowCreate) {
            loadCompanyOptions();
        }

        $('#company_id').on('change', function () {
            loadCompanyOptions();
        });

        $('#sample_form').on('submit', function (event) {
            event.preventDefault();
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
    });
})(jQuery);
</script>
