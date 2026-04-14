$('#company_type-table').DataTable().clear().destroy();

var table_table = $('#company_type-table').DataTable({
    initComplete: function () {
        this.api().columns([1]).every(function () {
            var column = this;
            var select = $('<select><option value=""></option></select>')
                .appendTo($(column.footer()).empty())
                .on('change', function () {
                    var val = $.fn.dataTable.util.escapeRegex(
                        $(this).val()
                    );

                    column
                        .search(val ? '^' + val + '$' : '', true, false)
                        .draw();
                });

            column.data().unique().sort().each(function (d, j) {
                select.append('<option value="' + d + '">' + d + '</option>');
                $('select').selectpicker('refresh');
            });
        });
    },
    responsive: true,
    fixedHeader: {
        header: true,
        footer: true
    },
    processing: true,
    serverSide: true,
    ajax: {
        url: "{{ route('company_type.index') }}",
    },
    columns: [
        {
            data: 'type_name',
            name: 'type_name',
        },
        {
            data: 'action',
            name: 'action',
            orderable: false
        }
    ],


    "order": [],
    'language': {
        'lengthMenu': '_MENU_ {{__("records per page")}}',
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
            'targets': [0, 1],
        },

    ],


    'select': { style: 'multi', selector: 'td:first-child' },
    'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],

});
new $.fn.dataTable.FixedHeader(table_table);



$('#company_type_submit').on('click', function (event) {
    event.preventDefault();
    let type_name = $('input[name="type_name"]').val();

    $.ajax({
        url: "{{ route('company_type.store') }}",
        method: "POST",
        data: { type_name: type_name },
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
                $('#company_type_form')[0].reset();
                $('#company_type-table').DataTable().ajax.reload();
            }
            $('.company_type_result').html(html).slideDown(300).delay(5000).slideUp(300);

        }
    });

});


$(document).on('click', '.company_type_edit', function () {
    var id = $(this).attr('id');
    $('.company_type_result').html('');

    var target = "{{ route('company_type.index') }}/" + id + '/edit';
    $.ajax({
        url: target,
        dataType: "json",
        success: function (html) {

            $('#type_name_edit').val(html.data.type_name);

            $('#hidden_company_type_id').val(html.data.id);
            $('#CompanyEditModal').modal('show');
        }
    })

});

$('#company_type_edit_submit').on('click', function (event) {
    event.preventDefault();
    let type_name_edit = $('input[name="type_name_edit"]').val();
    let hidden_company_type_id = $('#hidden_company_type_id').val();
    $.ajax({
        url: "{{ route('company_type.update') }}",
        method: "POST",
        data: { type_name_edit: type_name_edit, hidden_company_type_id: hidden_company_type_id },
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
                $('#company_type_form_edit')[0].reset();
                $('#CompanyEditModal').modal('hide');
                $('#company_type-table').DataTable().ajax.reload();
            }
            $('.company_type_result_edit').html(html).slideDown(300).delay(3000).slideUp(300);
            setTimeout(function () {
                $('#CompanyEditModal').modal('hide')
            }, 5000);
        }
    });
});



$(document).on('click', '.company_type_delete', function () {
    let delete_id = $(this).attr('id');
    let target = "{{ route('company_type.index') }}/" + delete_id + '/delete';
    if (confirm('{{__('Are You Sure you want to delete this data')}}')) {
        $.ajax({
            url: target,
            success: function (data) {
                var html = '';
                html = '<div class="alert alert-success">' + data.success + '</div>';
                setTimeout(function () {
                    $('#company_type-table').DataTable().ajax.reload();
                }, 2000);
                $('.company_type_result').html(html).slideDown(300).delay(3000).slideUp(300);
            }
        })
    }

});

$('#company_type_close').on('click', function () {
    $('#company_type_form')[0].reset();
    $('#company_type-table').DataTable().ajax.reload();
});
