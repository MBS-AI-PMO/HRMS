window.hrmsClientCompanyMap = window.hrmsClientCompanyMap || @json(collect($clients ?? [])->mapWithKeys(fn ($client) => [(string) $client->id => $client->resolved_company_id ?? null])->filter());

function refreshEmployeeOwnerSelectpicker($select) {
    if ($select.data('selectpicker')) {
        $select.selectpicker('destroy');
    }
    $select.selectpicker({ width: '100%', liveSearch: true, liveSearchStyle: 'contains' });
}

function repopulateEmployeeOwnerSelect($select, html) {
    if ($select.data('selectpicker')) {
        $select.selectpicker('destroy');
    }
    $select.html(html);
    $select.selectpicker({ width: '100%', liveSearch: true, liveSearchStyle: 'contains' });
}

function hrmsOwnerCsrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val() || '';
}

function getEmployeeFormCompanyId() {
    if ($('input[name="employee_owner_type"]:checked').val() === 'client') {
        var clientId = String($('#employee_client_id').val() || '');
        var map = window.hrmsClientCompanyMap || {};

        return String(map[clientId] || $('#employee_client_id option:selected').attr('data-company-id') || '');
    }

    return String($('#company_id').val() || '');
}

function loadEmployeeDepartments() {
    var ownerType = $('input[name="employee_owner_type"]:checked').val();
    var payload = {
        _token: hrmsOwnerCsrfToken(),
        dependent: 'department_name'
    };

    if (ownerType === 'client') {
        payload.client_id = $('#employee_client_id').val();
        var map = window.hrmsClientCompanyMap || {};
        payload.value = map[payload.client_id]
            || $('#employee_client_id option:selected').attr('data-company-id')
            || '';

        if (!payload.client_id) {
            return;
        }
    } else {
        payload.value = $('#company_id').val();

        if (!payload.value) {
            return;
        }
    }

    $.ajax({
        url: "{{ route('dynamic_department') }}",
        method: "POST",
        data: payload,
        success: function(result) {
            repopulateEmployeeOwnerSelect($('#department_id'), result);
            repopulateEmployeeOwnerSelect($('#designation_id'), '');
            loadEmployeeOfficeShifts();
        },
        error: function(xhr) {
            console.error('Department load failed', xhr.status, xhr.responseText);
            repopulateEmployeeOwnerSelect($('#department_id'), '');
            repopulateEmployeeOwnerSelect($('#designation_id'), '');
        }
    });
}

function applyEmployeeOfficeShiftSelection() {
    var shiftId = $('input[name="office_shift_id_hidden"]').val();
    if (shiftId) {
        $('#office_shift_id').selectpicker('val', shiftId);
    }
}

function loadEmployeeOfficeShifts() {
    var ownerType = $('input[name="employee_owner_type"]:checked').val();
    var payload = {
        _token: hrmsOwnerCsrfToken(),
        dependent: 'shift_name'
    };

    if (ownerType === 'client') {
        payload.client_id = $('#employee_client_id').val();

        if (!payload.client_id) {
            repopulateEmployeeOwnerSelect($('#office_shift_id'), '');
            return;
        }
    } else {
        payload.value = $('#company_id').val();

        if (!payload.value) {
            repopulateEmployeeOwnerSelect($('#office_shift_id'), '');
            return;
        }
    }

    $.ajax({
        url: "{{ route('dynamic_office_shifts') }}",
        method: "POST",
        data: payload,
        success: function(result) {
            repopulateEmployeeOwnerSelect($('#office_shift_id'), result);
            applyEmployeeOfficeShiftSelection();
        },
        error: function(xhr) {
            console.error('Office shift load failed', xhr.status, xhr.responseText);
            repopulateEmployeeOwnerSelect($('#office_shift_id'), '');
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
            repopulateEmployeeOwnerSelect($('#department_id'), '');
            repopulateEmployeeOwnerSelect($('#designation_id'), '');
            repopulateEmployeeOwnerSelect($('#office_shift_id'), '');
        }
    } else {
        $('#employee_owner_client_wrap').addClass('d-none');
        $('#employee_owner_company_wrap').removeClass('d-none');
        $('#employee_client_id').prop('disabled', true).removeAttr('name').removeAttr('required');
        $('#company_id').prop('disabled', false).attr('name', 'company_id').attr('required', 'required');

        if (!preserveValues) {
            $('#company_id').selectpicker('val', '');
            repopulateEmployeeOwnerSelect($('#department_id'), '');
            repopulateEmployeeOwnerSelect($('#designation_id'), '');
            repopulateEmployeeOwnerSelect($('#office_shift_id'), '');
        }
    }

    refreshEmployeeOwnerSelectpicker($('#company_id'));
    refreshEmployeeOwnerSelectpicker($('#employee_client_id'));
}

$('input[name="employee_owner_type"]').on('change', function() {
    toggleEmployeeOwnerType($(this).val(), false);
});

$(document).on('change changed.bs.select', '#company_id', function() {
    if ($('input[name="employee_owner_type"]:checked').val() !== 'company') {
        return;
    }

    if ($(this).val()) {
        loadEmployeeDepartments();
    }
});

$(document).on('change changed.bs.select', '#employee_client_id', function() {
    if ($('input[name="employee_owner_type"]:checked').val() !== 'client') {
        return;
    }

    if ($(this).val()) {
        loadEmployeeDepartments();
    }
});

$(document).on('change changed.bs.select', '#department_id', function() {
    if (!$(this).val()) {
        return;
    }

    $.ajax({
        url: "{{ route('dynamic_designation_department') }}",
        method: "POST",
        data: {
            value: $(this).val(),
            _token: hrmsOwnerCsrfToken(),
            designation_name: $(this).data('designation_name')
        },
        success: function(result) {
            repopulateEmployeeOwnerSelect($('#designation_id'), result);
        }
    });
});

(function initEmployeeOwnerFields() {
    var ownerType = $('#employee_owner_type_hidden').val() || 'company';
    toggleEmployeeOwnerType(ownerType, true);

    if (ownerType === 'company') {
        $('#company_id').selectpicker('val', $('input[name="company_id_hidden"]').val());
        if ($('#company_id').val()) {
            loadEmployeeDepartments();
        }
    } else {
        $('#employee_client_id').selectpicker('val', $('#employee_client_id_hidden').val());
        if ($('#employee_client_id').val()) {
            loadEmployeeDepartments();
        }
    }
})();
