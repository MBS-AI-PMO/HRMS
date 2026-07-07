<script type="text/javascript">
(function ($) {
    "use strict";

    var tableSelector = @json($tableSelector ?? '#my-location-table');

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
            },
            error: function () {
                alert('{{ __('Unable to load location details.') }}');
            }
        });
    });

    $(document).on('click', '.change_attendance', function () {
        var id = $(this).attr('id');
        $('#attendance_type_result').html('');
        $('#attendance_type_select').val('');
        $('#attendance_employee_summary').text('{{ __('Loading...') }}');

        $.ajax({
            url: "{{ url('/organization/locations') }}/" + id + "/attendance-type",
            dataType: 'json',
            success: function (res) {
                $('#attendance_location_id').val(res.location.id);
                $('#attendance_location_name').text(res.location.location_name);
                $('#attendance_employee_summary').text(
                    '{{ __('Active employees') }}: ' + res.total_employees +
                    ' ({{ __('General') }}: ' + (res.attendance_summary.general || 0) +
                    ', {{ __('Location Based') }}: ' + (res.attendance_summary.location_based || 0) + ')'
                );

                if ((res.attendance_summary.location_based || 0) >= (res.attendance_summary.general || 0)) {
                    $('#attendance_type_select').val('location_based');
                } else if ((res.attendance_summary.general || 0) > 0) {
                    $('#attendance_type_select').val('general');
                }

                $('#attendanceTypeModal').modal('show');
            },
            error: function () {
                alert('{{ __('Unable to load attendance type data.') }}');
            }
        });
    });

    $('#attendance_type_form').on('submit', function (event) {
        event.preventDefault();

        var attendanceType = $('#attendance_type_select').val();
        if (!attendanceType) {
            $('#attendance_type_result').html(
                '<div class="alert alert-danger">{{ __('Please select an attendance type.') }}</div>'
            ).slideDown(200).delay(4000).slideUp(200);
            return;
        }

        $.ajax({
            url: "{{ route('locations.update_attendance_type') }}",
            method: 'POST',
            dataType: 'json',
            data: {
                _token: $('input[name="_token"]', this).val(),
                location_id: $('#attendance_location_id').val(),
                attendance_type: attendanceType
            },
            success: function (data) {
                var html = '';
                if (data.errors) {
                    html = '<div class="alert alert-danger">';
                    for (var i = 0; i < data.errors.length; i++) {
                        html += '<p>' + data.errors[i] + '</p>';
                    }
                    html += '</div>';
                }
                if (data.success) {
                    html = '<div class="alert alert-success">' + data.success + '</div>';
                    setTimeout(function () {
                        $('#attendanceTypeModal').modal('hide');
                        $(tableSelector).DataTable().ajax.reload(null, false);
                    }, 1500);
                }
                $('#attendance_type_result').html(html).slideDown(200).delay(5000).slideUp(200);
            },
            error: function () {
                $('#attendance_type_result').html(
                    '<div class="alert alert-danger">{{ __('Something went wrong. Please try again.') }}</div>'
                ).slideDown(200).delay(5000).slideUp(200);
            }
        });
    });
})(jQuery);
</script>
