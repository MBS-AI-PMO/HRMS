$('#job_experience-table').DataTable().clear().destroy();

var table_table = $('#job_experience-table').DataTable({
    initComplete: function () {
        this.api().columns([1]).every(function () {
            var column = this;
            var select = $('<select><option value=""></option></select>')
                .appendTo($(column.footer()).empty())
                .on('change', function () {
                    var val = $.fn.dataTable.util.escapeRegex(
                        $(this).val()
                    );

                    column.search(val ? '^' + val + '$' : '', true, false).draw();
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
        url: "{{ route('job_experience.index') }}",
    },
    columns: [
        {
            data: 'title',
            name: 'title',
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



$('#job_experience_submit').on('click', function (event) {
    event.preventDefault();
    let jobExperienceTitle = $('input[name="job_experience_title"]').val();

    $.ajax({
        url: "{{ route('job_experience.store') }}",
        method: "POST",
        data: { title: jobExperienceTitle },
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
                $('#job_experience_form')[0].reset();
                $('#job_experience-table').DataTable().ajax.reload();
            }
            $('.job_experience_result').html(html).slideDown(300).delay(5000).slideUp(300);

        }
    });

});


$(document).on('click', '.job_experience_edit', function () {
    var id = $(this).attr('id');
    $('.job_experience_result').html('');
    var target = "{{ route('job_experience.index') }}/" + id + '/edit';
    $.ajax({
        url: target,
        dataType: "json",
        success: function (html) {
            console.log(html);
            $('input[name="job_experience_title_edit"]').val(html.data.title);
            $('#hidden_job_experience_id').val(html.data.id);
            $('#jobExperienceEditModal').modal('show');
        }
    });
});


$('#job_experience_edit_submit').on('click', function (event) {
    event.preventDefault();
    let job_experience_title_edit = $('input[name="job_experience_title_edit"]').val();
    let hidden_job_experience_id = $('#hidden_job_experience_id').val();
    $.ajax({
        url: "{{ route('job_experience.update') }}",
        method: "POST",
        data: { title: job_experience_title_edit, hidden_job_experience_id: hidden_job_experience_id },
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
                $('#job_experience_form_edit')[0].reset();
                $('#jobExperienceEditModal').modal('hide');
                $('#job_experience-table').DataTable().ajax.reload();
            }
            $('.job_experience_result_edit').html(html).slideDown(300).delay(3000).slideUp(300);
            setTimeout(function () {
                $('#jobExperienceEditModal').modal('hide')
            }, 5000);
        }
    });
});



$(document).on('click', '.job_experience_delete', function () {
    let delete_id = $(this).attr('id');
    let target = "{{ route('job_experience.index') }}/" + delete_id + '/delete';
    if (confirm('{{__('Are You Sure you want to delete this data')}}')) {
        $.ajax({
            url: target,
            success: function (data) {
                var html = '';
                html = '<div class="alert alert-success">' + data.success + '</div>';
                setTimeout(function () {
                    $('#job_experience-table').DataTable().ajax.reload();
                }, 2000);
                $('.job_experience_result').html(html).slideDown(300).delay(3000).slideUp(300);
            }
        })
    }

});

$('#job_experience_close').on('click', function () {
    $('#job_experience_form')[0].reset();
    $('#job_experience-table').DataTable().ajax.reload();
});
