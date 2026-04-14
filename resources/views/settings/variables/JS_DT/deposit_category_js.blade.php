$('#deposit_category-table').DataTable().clear().destroy();

var table_table = $('#deposit_category-table').DataTable({
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
        url: "{{ route('deposit_category.index') }}",
    },
    columns: [
        {
            data: 'name',
            name: 'name',
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



$('#deposit_category_submit').on('click', function (event) {
    event.preventDefault();
    let deposit_category_name = $('input[name="name"]').val();

    $.ajax({
        url: "{{ route('deposit_category.store') }}",
        method: "POST",
        data: { name: deposit_category_name },
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
                $('#deposit_category_form')[0].reset();
                $('#deposit_category-table').DataTable().ajax.reload();
            }
            $('.deposit_category_result').html(html).slideDown(300).delay(5000).slideUp(300);

        }
    });

});


$(document).on('click', '.deposit_category_edit', function () {
    var id = $(this).attr('id');
    $('.deposit_category_result').html('');
    var target = "{{ route('deposit_category.index') }}/" + id + '/edit';
    $.ajax({
        url: target,
        dataType: "json",
        success: function (html) {
            console.log(html);
            $('input[name="name_edit"]').val(html.data.name);
            $('#hidden_deposit_category_id').val(html.data.id);
            $('#depositCategoryEditModal').modal('show');
        }
    });
});


$('#deposit_category_edit_submit').on('click', function (event) {
    event.preventDefault();
    let nameEdit = $('input[name="name_edit"]').val();
    let hidden_deposit_category_id = $('#hidden_deposit_category_id').val();
    $.ajax({
        url: "{{ route('deposit_category.update') }}",
        method: "POST",
        data: { name: nameEdit, hidden_deposit_category_id: hidden_deposit_category_id },
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
                $('#deposit_category_form_edit')[0].reset();
                $('#depositCategoryEditModal').modal('hide');
                $('#deposit_category-table').DataTable().ajax.reload();
            }
            $('.deposit_category_result_edit').html(html).slideDown(300).delay(3000).slideUp(300);
            setTimeout(function () {
                $('#depositCategoryEditModal').modal('hide')
            }, 5000);
        }
    });
});



$(document).on('click', '.deposit_category_delete', function () {
    let delete_id = $(this).attr('id');
    let target = "{{ route('deposit_category.index') }}/" + delete_id + '/delete';
    if (confirm('{{__('Are You Sure you want to delete this data')}}')) {
        $.ajax({
            url: target,
            success: function (data) {
                var html = '';
                html = '<div class="alert alert-success">' + data.success + '</div>';
                setTimeout(function () {
                    $('#deposit_category-table').DataTable().ajax.reload();
                }, 2000);
                $('.deposit_category_result').html(html).slideDown(300).delay(3000).slideUp(300);
            }
        })
    }

});

$('#deposit_category_close').on('click', function () {
    $('#deposit_category_form')[0].reset();
    $('#deposit_category-table').DataTable().ajax.reload();
});
