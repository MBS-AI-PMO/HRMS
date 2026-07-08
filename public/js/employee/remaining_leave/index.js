let table_table = $('#employeeRemainingLeaveTable').DataTable({
    responsive: true,
    fixedHeader: {
        header: true,
        footer: true
    },
    processing: true,
    serverSide: true,
    ajax: {
        url: employeeLeaveTypeDetailUrl,
    },

    columns: [
        {
            data: 'leave_type',
            name: 'leave_type',

        },
        {
            data: 'allocated_day',
            name: 'allocated_day',

        },
        {
            data: 'remaining',
            name: 'remaining',

        },
    ],


    "order": [],
    'language': {
        'lengthMenu': '_MENU_records per page',
        "info": 'Showing _START_ - _END_ (_TOTAL_)',
        "search": 'Search',
        'paginate': {
            'previous': 'Previous',
            'next': 'Next'
        }
    },
    'columnDefs': [
        {
            "orderable": false,
            'targets': [0, 2],
        },
],


    'select': {style: 'multi', selector: 'td:first-child'},
    'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],

});
new $.fn.dataTable.FixedHeader(table_table);


