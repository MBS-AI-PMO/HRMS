    if ($.fn.DataTable.isDataTable('#employee_profile_complain-table')) {
        $('#employee_profile_complain-table').DataTable().clear().destroy();
    }

    let complainTable = $('#employee_profile_complain-table').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('employee_ticket.my') }}"
        },
        columns: [
            {
                data: 'ticket_details',
                name: 'ticket_details',
                render: function (data, type, row) {
                    return (data || '') + "<br><span class='badge badge-success'>" + (row.ticket_status || '') + "</span>";
                }
            },
            { data: 'subject', name: 'subject' },
            { data: 'ticket_priority', name: 'ticket_priority' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
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
            { orderable: false, targets: [4] }
        ],
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });

    $('#employee_complain-tab').off('shown.bs.tab.complain').on('shown.bs.tab.complain', function () {
        complainTable.columns.adjust().draw(false);
    });

