$('#employee_leave-table').DataTable().clear().destroy();
var date = $('.date');
date.datepicker({
format: '{{ env('Date_Format_JS')}}',
autoclose: true,
todayHighlight: true
});







        let table_table = $('#employee_leave-table').DataTable({
            responsive: true,
            fixedHeader: {
                header: true,
                footer: true
            },
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('employee_leave.index',$employee->id) }}",
            },

            columns: [
                {
                    data: null,
                    render: function (data) {
                        return data.leave_type + '<br><div class="badge badge-success">' + data.status + '</div><br><b><i>Reason:</i></b>' + data.leave_reason;
                    }

                },

                {
                    data: 'department',
                    name: 'department',

                },

                {
                    data: null,
                    render: function ( data) {

                        return data.start_date + ' to ' + data.end_date
                            + "<br>" +' Total '+ data.total_days + ' Days ' ;


                    }

                },
                {
                    data: 'created_at',
                    name: 'created_at',

                },
                {
                    data: 'approved_by_name',
                    name: 'approved_by_name',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        if (!data || row.status === 'pending') {
                            return '-';
                        }

                        let label = row.status === 'rejected' ? '{{ __('Rejected by') }}' : '{{ __('Approved by') }}';
                        return '<span class="text-muted small d-block">' + label + '</span>' + data;
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false
                }
            ],


            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{__('records per page')}}',
                "info": '{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)',
                "search": '{{trans("file.Search")}}',
                'paginate': {
                    'previous': '{{trans("file.Previous")}}',
                    'next': '{{trans("file.Next")}}'
                }
            },
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [0, 5],
                },
        ],


            'select': {style: 'multi', selector: 'td:first-child'},
            'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],

        });
        new $.fn.dataTable.FixedHeader(table_table);

$(document).on('click', '.show_leave', function () {

    let id = $(this).attr('id');

    let target = '{{route('employee_leave.details')}}/' + id;

    $.ajax({
        url: target,
        dataType: "json",
        success: function (result) {

            hrmsFillLeaveInfoModal(result, {
                avatar: '#leave_info_avatar',
                employee: '#leave_employee_id_show',
                department: '#leave_department_id_show',
                company: '#leave_company_id_show',
                type: '#leave_leave_type_id',
                startDate: '#leave_start_date_id',
                endDate: '#leave_end_date_id',
                appliedDate: '#leave_applied_date_id',
                totalDays: '#leave_total_days_id',
                status: '#leave_status_id',
                reason: '#leave_leave_reason_id',
                remarksSection: '#leave_remarks_section',
                remarks: '#leave_remarks_id',
                approvedByRow: '#leave_approved_by_row',
                approvedByLabel: '#leave_approved_by_label',
                approvedById: '#leave_approved_by_id',
                halfDay: '#leave_is_half_id',
                notify: '#leave_is_notify_id'
            }, {
                approvedBy: '{{ __('Approved By') }}',
                rejectedBy: '{{ __('Rejected By') }}',
                yes: '{{ __('Yes') }}',
                no: '{{ __('No') }}',
                on: '{{ __('On') }}',
                off: '{{ __('Off') }}'
            });

            $('#leave_model').modal('show');
            $('#leave_model .modal-title').text("{{__('Leave Info')}}");
        }
    });
});


