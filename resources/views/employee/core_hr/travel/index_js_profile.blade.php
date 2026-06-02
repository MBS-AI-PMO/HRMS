    var $profileTravelPane = $('#Profile_travel');
    var $profileTravelTable = $profileTravelPane.find('#profile-travel-table');

    if ($.fn.DataTable.isDataTable($profileTravelTable)) {
        $profileTravelTable.DataTable().clear().destroy();
    }

    window.profileTravelTable = $profileTravelTable.DataTable({
        responsive: false,
        processing: true,
        serverSide: false,
        ajax: {
            url: "{{ route('profile.travels.index') }}",
            data: function (d) {
                d.employee_id = "{{ $employee->id }}";
            },
            dataSrc: 'data',
            error: function (xhr) {
                console.error('Travel list failed', xhr.status, xhr.responseText);
            }
        },
        columns: [
            {
                data: 'summary',
                name: 'summary',
                orderable: false,
                searchable: false,
                defaultContent: '',
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return row.purpose_of_visit || '';
                    }

                    if (data) {
                        return data;
                    }

                    var expected = row.expected_budget || '0';
                    var actual = row.actual_budget || '0';
                    var purpose = row.purpose_of_visit || '';
                    var status = row.status || '';

                    return purpose
                        + '<br><br><b><i>{{__('Expected Budget :')}}</i></b> ' + expected
                        + '<br><b><i>{{__('Actual Budget :')}}</i></b> ' + actual
                        + '<br><div class="badge badge-success">' + status + '</div>';
                }
            },
            {
                data: 'place_of_visit',
                name: 'place_of_visit',
            },
            {
                data: 'start_date',
                name: 'start_date',
            },
            {
                data: 'end_date',
                name: 'end_date',
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                defaultContent: '',
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return '';
                    }

                    if (data) {
                        return data;
                    }

                    return '<button type="button" name="show_travel" id="' + row.id + '" class="show_travel btn btn-success btn-sm"><i class="dripicons-preview"></i></button>';
                }
            }
        ],
        order: [],
        language: {
            lengthMenu: '_MENU_ {{__('records per page')}}',
            info: '{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)',
            search: '{{trans("file.Search")}}',
            paginate: {
                previous: '{{trans("file.Previous")}}',
                next: '{{trans("file.Next")}}'
            }
        },
        columnDefs: [
            {
                orderable: false,
                targets: [0, 4],
            },
        ],
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
        initComplete: function () {
            this.api().columns.adjust().draw(false);
        }
    });

    $('#employee_travel-tab').off('shown.bs.tab.travel').on('shown.bs.tab.travel', function () {
        if (window.profileTravelTable) {
            window.profileTravelTable.columns.adjust().draw(false);
        }
    });

    $profileTravelPane.off('click', '.show_travel').on('click', '.show_travel', function () {
        var id = $(this).attr('id');
        var target = "{{ url('/profile/travels') }}/" + id;

        $.ajax({
            url: target,
            dataType: 'json',
            success: function (result) {
                $('#profile_travel_company_id_show').html(result.company_name);
                $('#profile_travel_employee_id_show').html(result.employee_name);
                $('#profile_travel_description_show').html(result.data.description);
                $('#profile_travel_start_date_show').html(result.data.start_date);
                $('#profile_travel_end_date_show').html(result.data.end_date);
                $('#profile_travel_purpose_of_visit_show').html(result.data.purpose_of_visit);
                $('#profile_travel_place_of_visit_show').html(result.data.place_of_visit);
                $('#profile_travel_travel_mode_show').html(result.data.travel_mode);
                $('#profile_travel_travel_type_show').html(result.arrangement_name);
                $('#profile_travel_expected_budget_show').html(result.data.expected_budget);
                $('#profile_travel_actual_budget_show').html(result.data.actual_budget);
                $('#profile_travel_status_show').html(result.data.status);

                $('#profile-travel-modal').modal('show');
            }
        });
    });
