<script type="text/javascript">
(function ($) {
    "use strict";

    var tableSelector = @json($tableSelector ?? '#my-location-table');

    function toggleEmployeeSelection() {
        if ($('#assign_scope_all').is(':checked')) {
            $('#employee_selection_wrap').hide();
        } else {
            $('#employee_selection_wrap').show();
        }
    }

    function renderOfficeShiftRows(companies, selectedShifts) {
        selectedShifts = selectedShifts || {};

        if (!companies || !companies.length) {
            $('#office_shift_rows').html('{{ __('No office shift found for selected companies.') }}');
            return;
        }

        var rows = '';

        companies.forEach(function (company, index) {
            var shiftOptions = '<option value="">{{ __('Select Shift') }}</option>';
            var selectedShiftId = String(selectedShifts[company.id] || '');

            (company.shifts || []).forEach(function (shift) {
                var selected = selectedShiftId === String(shift.id) ? 'selected' : '';
                shiftOptions += '<option value="' + shift.id + '" ' + selected + '>' + shift.shift_name + '</option>';
            });

            rows += '<div class="row align-items-center mb-2">' +
                '<div class="col-md-5"><strong>' + company.company_name + '</strong></div>' +
                '<div class="col-md-7">' +
                '<select class="form-control location-shift-select" ' +
                'name="shifts[' + index + '][office_shift_id]" ' +
                'data-company-id="' + company.id + '">' +
                shiftOptions +
                '</select>' +
                '<input type="hidden" name="shifts[' + index + '][company_id]" value="' + company.id + '">' +
                '</div>' +
                '</div>';
        });

        $('#office_shift_rows').html(rows || '<span class="text-danger">{{ __('No office shift found for selected companies.') }}</span>');
    }

    function loadEmployeesByCompanies(companyIds, keepSelected, selectedLocationHeads, selectedShifts) {
        if (!companyIds || !companyIds.length) {
            $('#employee_ids').empty().selectpicker('refresh');
            $('#location_head').empty().selectpicker('refresh');
            renderOfficeShiftRows([]);
            return;
        }

        var prevEmployees = keepSelected ? ($('#employee_ids').val() || []) : [];
        var prevHeads = keepSelected
            ? (selectedLocationHeads || $('#location_head').val() || []).map(String)
            : [];
        var prevShifts = selectedShifts || {};

        $.ajax({
            url: "{{ route('locations.employees_by_companies') }}",
            method: "GET",
            data: { company_ids: companyIds },
            dataType: "json",
            success: function (res) {
                var employeeOpts = '';
                var headOpts = '';

                (res.employees || []).forEach(function (emp) {
                    var employeeSelected = prevEmployees.includes(String(emp.id)) ? 'selected' : '';
                    var headSelected = prevHeads.includes(String(emp.id)) ? 'selected' : '';

                    employeeOpts += '<option value="' + emp.id + '" ' + employeeSelected + '>' + emp.full_name + '</option>';
                    headOpts += '<option value="' + emp.id + '" ' + headSelected + '>' + emp.full_name + '</option>';
                });

                $('#employee_ids').html(employeeOpts).selectpicker('refresh');
                $('#location_head').html(headOpts).selectpicker('refresh');
                renderOfficeShiftRows(res.companies || [], prevShifts);
            }
        });
    }

    $('#sample_form').on('submit', function (event) {
        event.preventDefault();

        $.ajax({
            url: "{{ route('locations.update') }}",
            method: "POST",
            data: new FormData(this),
            contentType: false,
            cache: false,
            processData: false,
            dataType: "json",
            success: function (data) {
                var html = '';
                if (data.errors) {
                    html = '<div class="alert alert-danger">';
                    for (var count = 0; count < data.errors.length; count++) {
                        html += '<p>' + data.errors[count] + '</p>';
                    }
                    html += '</div>';
                }
                if (data.success) {
                    html = '<div class="alert alert-success">' + data.success + '</div>';
                    setTimeout(function () {
                        $('#formModal').modal('hide');
                        $(tableSelector).DataTable().ajax.reload(null, false);
                    }, 1500);
                }
                $('#form_result').html(html).slideDown(300).delay(5000).slideUp(300);
            },
            error: function () {
                $('#form_result').html(
                    '<div class="alert alert-danger">{{ __('Something went wrong. Please try again.') }}</div>'
                ).slideDown(300).delay(5000).slideUp(300);
            }
        });
    });

    $(document).on('click', '.edit', function () {
        var id = $(this).attr('id');
        $('#form_result').html('');

        $.ajax({
            url: "{{ url('/organization/locations/edit') }}/" + id,
            dataType: "json",
            success: function (html) {
                if (html.errors) {
                    alert((html.errors || []).join('\n'));
                    return;
                }

                $('#location_name').val(html.data.location_name);
                $('#company_ids').selectpicker('val', html.company_ids || []);
                $('#address1').val(html.data.address1);
                $('#address2').val(html.data.address2);
                $('#city').val(html.data.city);
                $('#state').val(html.data.state);
                $('#country').selectpicker('val', html.data.country);
                $('#zip').val(html.data.zip);
                $('#latitude').val(html.data.latitude);
                $('#longitude').val(html.data.longitude);
                $('#max_radius').val(html.data.max_radius);
                $('#hidden_id').val(html.data.id);
                $('#formModal').modal('show');
                loadEmployeesByCompanies(html.company_ids || [], true, html.location_head_ids || [], {});
            },
            error: function () {
                alert('{{ __('Unable to load location details.') }}');
            }
        });
    });

    $(document).on('click', '.assign_shift', function () {
        var id = $(this).attr('id');
        $('#shift_assign_result').html('');
        $('#shift_assign_rows').html('<tr><td colspan="3" class="text-center">{{ __('Loading...') }}</td></tr>');

        $.ajax({
            url: "{{ url('/organization/locations') }}/" + id + "/shift-assignment",
            dataType: "json",
            success: function (res) {
                if (res.errors) {
                    $('#shift_assign_rows').html(
                        '<tr><td colspan="3" class="text-center text-danger">' + (res.errors[0] || '{{ __('You are not authorized') }}') + '</td></tr>'
                    );
                    $('#shiftAssignModal').modal('show');
                    return;
                }

                $('#shift_location_id').val(res.location.id);
                $('#shift_location_name').text(res.location.location_name);
                $('#shift_employee_summary').text(
                    '{{ __('Total active employees at this location') }}: ' + (res.total_employees || 0)
                );

                var rows = '';
                (res.companies || []).forEach(function (company, index) {
                    var shiftOptions = '<option value="">{{ __('Select Shift') }}</option>';
                    (company.shifts || []).forEach(function (shift) {
                        shiftOptions += '<option value="' + shift.id + '">' + shift.shift_name + '</option>';
                    });

                    rows += '<tr>' +
                        '<td>' + company.company_name + '</td>' +
                        '<td>' + (company.employee_count || 0) + '</td>' +
                        '<td>' +
                        '<select class="form-control shift-company-select" ' +
                        'name="shifts[' + index + '][office_shift_id]" ' +
                        'data-company-id="' + company.id + '" required>' +
                        shiftOptions +
                        '</select>' +
                        '<input type="hidden" name="shifts[' + index + '][company_id]" value="' + company.id + '">' +
                        '</td>' +
                        '</tr>';
                });

                if (!rows) {
                    rows = '<tr><td colspan="3" class="text-center text-danger">{{ __('No companies linked to this location.') }}</td></tr>';
                    $('#shift_assign_submit').prop('disabled', true);
                } else {
                    $('#shift_assign_submit').prop('disabled', false);
                }

                $('#shift_assign_rows').html(rows);
                $('#shiftAssignModal').modal('show');
            },
            error: function () {
                $('#shift_assign_rows').html(
                    '<tr><td colspan="3" class="text-center text-danger">{{ __('Unable to load shift assignment data.') }}</td></tr>'
                );
                $('#shiftAssignModal').modal('show');
            }
        });
    });

    $('#shift_assign_form').on('submit', function (event) {
        event.preventDefault();

        var shifts = [];
        $('#shift_assign_rows .shift-company-select').each(function () {
            var shiftId = $(this).val();
            var companyId = $(this).data('company-id');
            if (shiftId) {
                shifts.push({
                    company_id: companyId,
                    office_shift_id: shiftId
                });
            }
        });

        if (!shifts.length) {
            $('#shift_assign_result').html(
                '<div class="alert alert-danger">{{ __('Please select at least one shift.') }}</div>'
            ).slideDown(200);
            return;
        }

        $.ajax({
            url: "{{ route('locations.assign_shift') }}",
            method: "POST",
            data: {
                _token: $('input[name="_token"]').val(),
                location_id: $('#shift_location_id').val(),
                shifts: shifts
            },
            dataType: "json",
            success: function (data) {
                var html = '';
                if (data.errors) {
                    html = '<div class="alert alert-danger">';
                    (data.errors || []).forEach(function (error) {
                        html += '<p>' + error + '</p>';
                    });
                    html += '</div>';
                }
                if (data.success) {
                    html = '<div class="alert alert-success">' + data.success + '</div>';
                    setTimeout(function () {
                        $('#shiftAssignModal').modal('hide');
                    }, 1500);
                }
                $('#shift_assign_result').html(html).slideDown(200).delay(5000).slideUp(200);
            }
        });
    });

    $('#company_ids').on('changed.bs.select', function () {
        loadEmployeesByCompanies($(this).val() || [], false);
    });

    $('input[name="assign_scope"]').on('change', toggleEmployeeSelection);
    toggleEmployeeSelection();
})(jQuery);
</script>
