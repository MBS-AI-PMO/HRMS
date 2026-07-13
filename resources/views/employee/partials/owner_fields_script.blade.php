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

function getEmployeeDepartmentHiddenVal() {
    return String($('input[name="department_id_hidden"]').val() || '');
}

function getEmployeeDesignationHiddenVal() {
    return String($('input[name="designation_id_hidden"]').val() || '');
}

var hrmsApplyingEmployeeOwnerSelections = false;

function getEmployeeFormCompanyId() {
    if ($('input[name="employee_owner_type"]:checked').val() === 'client') {
        var clientId = String($('#employee_client_id').val() || '');
        var map = window.hrmsClientCompanyMap || {};

        return String(map[clientId] || $('#employee_client_id option:selected').attr('data-company-id') || '');
    }

    return String($('#company_id').val() || '');
}

function applyEmployeeOfficeShiftSelection() {
    var shiftId = $('input[name="office_shift_id_hidden"]').val();
    if (shiftId) {
        $('#office_shift_id').selectpicker('val', shiftId);
    }
}

function loadEmployeeDesignations(departmentId, onComplete) {
    if (!departmentId) {
        repopulateEmployeeOwnerSelect($('#designation_id'), '');
        if (typeof onComplete === 'function') {
            onComplete();
        }
        return;
    }

    $.ajax({
        url: "{{ route('dynamic_designation_department') }}",
        method: "POST",
        data: {
            value: departmentId,
            _token: hrmsOwnerCsrfToken(),
            designation_name: $('#department_id').data('designation_name')
        },
        success: function(result) {
            repopulateEmployeeOwnerSelect($('#designation_id'), result);
            var designationId = getEmployeeDesignationHiddenVal();
            if (designationId) {
                $('#designation_id').selectpicker('val', designationId);
            }
            if (typeof onComplete === 'function') {
                onComplete();
            }
        },
        error: function(xhr) {
            console.error('Designation load failed', xhr.status, xhr.responseText);
            repopulateEmployeeOwnerSelect($('#designation_id'), '');
            if (typeof onComplete === 'function') {
                onComplete();
            }
        }
    });
}

function applyEmployeeDepartmentAndDesignationSelections(onComplete) {
    hrmsApplyingEmployeeOwnerSelections = true;
    var departmentId = getEmployeeDepartmentHiddenVal();

    if (departmentId) {
        $('#department_id').selectpicker('val', departmentId);
    } else {
        departmentId = String($('#department_id').val() || '');
    }

    loadEmployeeDesignations(departmentId, function() {
        hrmsApplyingEmployeeOwnerSelections = false;
        if (typeof onComplete === 'function') {
            onComplete();
        }
    });
}

function loadEmployeeDepartments(onComplete) {
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
            if (typeof onComplete === 'function') {
                onComplete();
            }
            return;
        }
    } else {
        payload.value = $('#company_id').val();

        if (!payload.value) {
            if (typeof onComplete === 'function') {
                onComplete();
            }
            return;
        }
    }

    $.ajax({
        url: "{{ route('dynamic_department') }}",
        method: "POST",
        data: payload,
        success: function(result) {
            repopulateEmployeeOwnerSelect($('#department_id'), result);
            applyEmployeeDepartmentAndDesignationSelections(function() {
                loadEmployeeOfficeShifts(onComplete);
            });
        },
        error: function(xhr) {
            console.error('Department load failed', xhr.status, xhr.responseText);
            applyEmployeeDepartmentAndDesignationSelections(function() {
                loadEmployeeOfficeShifts(onComplete);
            });
        }
    });
}

function loadEmployeeOfficeShifts(onComplete) {
    var ownerType = $('input[name="employee_owner_type"]:checked').val();
    var payload = {
        _token: hrmsOwnerCsrfToken(),
        dependent: 'shift_name'
    };

    if (ownerType === 'client') {
        payload.client_id = $('#employee_client_id').val();

        if (!payload.client_id) {
            repopulateEmployeeOwnerSelect($('#office_shift_id'), '');
            if (typeof onComplete === 'function') {
                onComplete();
            }
            return;
        }
    } else {
        payload.value = $('#company_id').val();

        if (!payload.value) {
            repopulateEmployeeOwnerSelect($('#office_shift_id'), '');
            if (typeof onComplete === 'function') {
                onComplete();
            }
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
            if (typeof onComplete === 'function') {
                onComplete();
            }
        },
        error: function(xhr) {
            console.error('Office shift load failed', xhr.status, xhr.responseText);
            repopulateEmployeeOwnerSelect($('#office_shift_id'), '');
            if (typeof onComplete === 'function') {
                onComplete();
            }
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
    if (hrmsApplyingEmployeeOwnerSelections || !$(this).val()) {
        return;
    }

    loadEmployeeDesignations($(this).val());
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
