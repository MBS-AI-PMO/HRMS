(function () {
    if ($.fn.DataTable.isDataTable('#employee_leave-table')) {
        $('#employee_leave-table').DataTable().clear().destroy();
    }
    if ($.fn.DataTable.isDataTable('#employee_wfh_leave-table')) {
        $('#employee_wfh_leave-table').DataTable().clear().destroy();
    }

    var date = $('.date');
    date.datepicker({
        format: '{{ env('Date_Format_JS') }}',
        autoclose: true,
        todayHighlight: true
    });

    var leaveColumns = [
        {
            data: null,
            render: function (data) {
                let status = (data.status || '').toLowerCase();
                let statusBadge = '';

                if (status === 'pending') {
                    statusBadge = '<span class="badge badge-warning">Pending</span>';
                } else if (status === 'approved') {
                    statusBadge = '<span class="badge badge-success">Approved</span>';
                } else if (status === 'rejected') {
                    statusBadge = '<span class="badge badge-danger">Rejected</span>';
                } else {
                    statusBadge = '<span class="badge badge-secondary">' + data.status + '</span>';
                }

                let reason = (data.leave_reason || '').trim();

                return data.leave_type + "<br>" + statusBadge
                    + (reason ? "<br><b><i>Reason:</i></b> " + reason : '');
            }
        },
        {
            data: 'department',
            name: 'department',
        },
        {
            data: null,
            render: function (data) {
                return data.start_date + ' to ' + data.end_date
                    + "<br>" + ' Total ' + data.total_days + ' Days ';
            }
        },
        {
            data: 'created_at',
            name: 'created_at',
        },
        {
            data: 'action',
            name: 'action',
            orderable: false
        }
    ];

    var dtOptions = {
        responsive: true,
        fixedHeader: {
            header: true,
            footer: true
        },
        processing: true,
        serverSide: true,
        columns: leaveColumns,
        order: [],
        language: {
            lengthMenu: '_MENU_ {{ __('records per page') }}',
            info: '{{ trans("file.Showing") }} _START_ - _END_ (_TOTAL_)',
            search: '{{ trans("file.Search") }}',
            paginate: {
                previous: '{{ trans("file.Previous") }}',
                next: '{{ trans("file.Next") }}'
            }
        },
        columnDefs: [
            {
                orderable: false,
                targets: [0, 4],
            },
        ],
        select: { style: 'multi', selector: 'td:first-child' },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
    };

    var tableLeave = $('#employee_leave-table').DataTable($.extend({}, dtOptions, {
        ajax: {
            url: "{{ route('employee_leave.index', $employee->id) }}",
        },
    }));
    new $.fn.dataTable.FixedHeader(tableLeave);

    var tableWfh = $('#employee_wfh_leave-table').DataTable($.extend({}, dtOptions, {
        ajax: {
            url: "{{ route('employee_leave.index', $employee->id) }}?wfh=1",
        },
    }));
    new $.fn.dataTable.FixedHeader(tableWfh);

    var openLeaveModal = function (infoTitle) {
        var id = $(this).attr('id');
        var target = '{{ route('employee_leave.details') }}/' + id;

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

                hrmsOpenLeaveInfoModal('#leave_model', infoTitle || "{{ __('Leave Info') }}");
            },
            error: function () {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            }
        });
    };

    $(document).off('click', '.show_leave').on('click', '.show_leave', function () {
        openLeaveModal.call(this, "{{ __('Leave Info') }}");
    });

    $(document).off('click', '.show_wfh_leave').on('click', '.show_wfh_leave', function () {
        openLeaveModal.call(this, "{{ __('WFH Info') }}");
    });
})();