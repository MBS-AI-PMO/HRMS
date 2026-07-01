function refreshEmployeeOwnerSelectpicker($select) {
    if ($select.data('selectpicker')) {
        $select.selectpicker('destroy');
    }
    $select.selectpicker({ width: '100%' });
}

function getEmployeeFormCompanyId() {
    if ($('input[name="employee_owner_type"]:checked').val() === 'client') {
        return String($('#employee_client_id option:selected').data('company-id') || '');
    }

    return String($('#company_id').val() || '');
}

function loadEmployeeDepartments(companyId) {
    if (!companyId) {
        return;
    }

    $.ajax({
        url: "{{ route('dynamic_department') }}",
        method: "POST",
        data: {
            value: companyId,
            _token: $('input[name="_token"]').val(),
            dependent: 'department_name'
        },
        success: function(result) {
            $('#department_id').html(result);
            $('#designation_id').html('');
            refreshEmployeeOwnerSelectpicker($('#department_id'));
            refreshEmployeeOwnerSelectpicker($('#designation_id'));
        }
    });
}

function loadEmployeeOfficeShifts(companyId) {
    if (!companyId) {
        return;
    }

    $.ajax({
        url: "{{ route('dynamic_office_shifts') }}",
        method: "POST",
        data: {
            value: companyId,
            _token: $('input[name="_token"]').val(),
            dependent: 'shift_name'
        },
        success: function(result) {
            $('#office_shift_id').html(result);
            refreshEmployeeOwnerSelectpicker($('#office_shift_id'));
        }
    });
}

function toggleEmployeeOwnerType(type, preserveValues) {
    type = type || 'company';

    $('input[name="employee_owner_type"][value="' + type + '"]').prop('checked', true);

    if (type === 'client') {
        $('#employee_owner_company_wrap').addClass('d-none');
        $('#employee_owner_client_wrap').removeClass('d-none');
        $('#company_id').prop('disabled', true).removeAttr('name').removeAttr('required');
        $('#employee_client_id').prop('disabled', false).attr('name', 'client_id').attr('required', 'required');

        if (!preserveValues) {
            $('#employee_client_id').selectpicker('val', '');
            $('#department_id').html('');
            $('#designation_id').html('');
            $('#office_shift_id').html('');
        }
    } else {
        $('#employee_owner_client_wrap').addClass('d-none');
        $('#employee_owner_company_wrap').removeClass('d-none');
        $('#employee_client_id').prop('disabled', true).removeAttr('name').removeAttr('required');
        $('#company_id').prop('disabled', false).attr('name', 'company_id').attr('required', 'required');

        if (!preserveValues) {
            $('#company_id').selectpicker('val', '');
            $('#department_id').html('');
            $('#designation_id').html('');
            $('#office_shift_id').html('');
        }
    }

    $('.employee-owner-panel .selectpicker').each(function() {
        if ($(this).data('selectpicker')) {
            $(this).selectpicker('render');
        }
    });
}

$('input[name="employee_owner_type"]').on('change', function() {
    toggleEmployeeOwnerType($(this).val(), false);
});

$('#employee_client_id').on('changed.bs.select', function() {
    var companyId = getEmployeeFormCompanyId();
    loadEmployeeDepartments(companyId);
    loadEmployeeOfficeShifts(companyId);
});

(function initEmployeeOwnerFields() {
    var ownerType = $('#employee_owner_type_hidden').val() || 'company';
    toggleEmployeeOwnerType(ownerType, true);

    if (ownerType === 'company') {
        $('#company_id').selectpicker('val', $('input[name="company_id_hidden"]').val());
    } else {
        $('#employee_client_id').selectpicker('val', $('#employee_client_id_hidden').val());
    }
})();
