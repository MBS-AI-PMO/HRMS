@push('scripts')
<script>
    (function($) {
        "use strict";

        $(window).on('load',function () {
            let calendarEl = document.getElementById('calendar');

            let calendar = new FullCalendar.Calendar(calendarEl, {
                aspectRatio: 1,
                plugins: ['interaction', 'dayGrid', 'timeGrid', 'list'],
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },


                editable: true,
                selectable: false,
                navLinks: true, // can click day/week names to navigate views
                events: {
                    url: '{{route('calendar.load')}}',
                    textColor: 'white',
                    success: function (result) {
                        console.log(result);
                    }
                },

                eventTimeFormat: { // like '14:30:00'
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: true
                },

                eventClick: function (info) {
                    var detailUrl = info.event.groupId;
                    if (!detailUrl) {
                        return;
                    }

                    info.jsEvent.preventDefault();
                    $('#table_data').html('');

                    $.ajax({
                        url: detailUrl,
                        dataType: 'json',
                        success: function (data) {
                            var label = (info.event.extendedProps && info.event.extendedProps.overlap)
                                || info.event.overlap
                                || info.event.title
                                || '';
                            $('#model_name').html(label);
                            $('#details_model').modal('show');
                            if (data && data.data) {
                                for (let key in data.data) {
                                    if (Object.prototype.hasOwnProperty.call(data.data, key)) {
                                        $('#table_data').append('<tr><th>' + key + '</th><td>' + (data.data[key] ?? '') + '</td></tr>');
                                    }
                                }
                            }
                        },
                        error: function (xhr) {
                            var message = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message))
                                || '{{ __('Request failed. Please try again.') }}';
                            if (window.hrmsSwalResponse) {
                                window.hrmsSwalResponse(null, { fallbackError: message });
                            } else {
                                alert(message);
                            }
                        }
                    });
                },

            });
            $('.close').on('click', function () {
                calendar.refetchEvents();
            });
            calendar.render();

            let date = $('.date');
            date.datepicker({
                format: '{{ config('variable.date_format_js', 'dd-mm-yyyy')}}',
                autoclose: true,
                todayHighlight: true
            });

            $('[data-record="0"]').on('click', function (e) {
                $('#holidayModal').modal('show');
            });

            $('[data-record="1"]').on('click', function (e) {
                $('#leaveModal').modal('show');
            });

            $('[data-record="2"]').on('click', function (e) {
                $('#travelModal').modal('show');
            });

            $('[data-record="3"]').on('click', function (e) {
                $('#trainingModal').modal('show');
            });

            $('[data-record="4"]').on('click', function (e) {
                $('#projectModal').modal('show');
            });

            $('[data-record="5"]').on('click', function (e) {
                $('#taskModal').modal('show');
            });

            $('[data-record="6"]').on('click', function (e) {
                $('#eventModal').modal('show');
            });

            $('[data-record="7"]').on('click', function (e) {
                $('#meetingModal').modal('show');
            });


            $('#holiday_sample_form').on('submit', function (event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('holidays.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (var count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.error) {
                            html = '<div class="alert alert-danger">' + data.error + '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#holiday_sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('.date').datepicker('update');
                        }
                        $('#holiday_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
            });

            $('#leave_sample_form').on('submit', function (event) {
                event.preventDefault();

                let start_date = $("#leave_start_date").datepicker('getDate');
                let end_date = $("#leave_end_date").datepicker('getDate');
                let dayDiff = Math.ceil((end_date - start_date) / (1000 * 60 * 60 * 24)) + 1;

                $('#diff_date_hidden').val(dayDiff);


                $.ajax({
                    url: "{{ route('leaves.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (var count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.limit) {
                            html = '<div class="alert alert-danger">' + data.limit + '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#leave_sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('.date').datepicker('update');
                        }
                        $('#leave_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
            });

            $('#travel_sample_form').on('submit', function (event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('travels.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (var count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.error) {
                            html = '<div class="alert alert-danger">' + data.error + '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#travel_sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('.date').datepicker('update');
                        }
                        $('#travel_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
            });

            $('#training_sample_form').on('submit', function (event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('training_lists.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (let count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#training_sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('.date').datepicker('update');
                        }
                        $('#training_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
            });

            $('#project_sample_form').on('submit', function (event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('projects.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (var count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.error) {
                            html = '<div class="alert alert-danger">' + data.error + '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#project_sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('.js-example-responsive').val(null).trigger('change');
                        }
                        $('#project_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    },
                    error: function (xhr) {
                        var data = xhr.responseJSON || {};
                        var message = (data.errors && data.errors.length)
                            ? data.errors.join('<br>')
                            : (data.error || data.message || '{{ __('Request failed. Please try again.') }}');
                        $('#project_form_result').html('<div class="alert alert-danger">' + message + '</div>')
                            .slideDown(300).delay(5000).slideUp(300);
                        if (window.hrmsSwalResponse) {
                            window.hrmsSwalResponse(data.errors || data.error ? data : null, {
                                fallbackError: $('<div>').html(message).text()
                            });
                        }
                    }
                })
            });

            function loadCalendarProjectCategories(clientId) {
                var $category = $('#project_category_id');
                if (!$category.length) {
                    return;
                }
                $.ajax({
                    url: "{{ route('dynamic_project_categories') }}",
                    method: "POST",
                    data: {
                        value: clientId || '',
                        _token: $('input[name="_token"]').val()
                    },
                    success: function (result) {
                        $category.html(result);
                        $category.selectpicker('refresh');
                    }
                });
            }

            function loadCalendarProjectEmployees(clientId) {
                var $employee = $('#project_employee_id');
                if (!$employee.length) {
                    return;
                }
                $employee.html('').val(null).trigger('change');
                if (!clientId) {
                    return;
                }
                $.ajax({
                    url: "{{ route('dynamic_project_employees') }}",
                    method: "POST",
                    data: {
                        value: clientId,
                        _token: $('input[name="_token"]').val(),
                        first_name: 'first_name',
                        last_name: 'last_name'
                    },
                    success: function (result) {
                        $employee.html(result).trigger('change');
                    }
                });
            }

            $(document).on('changed.bs.select', '#project_client_id', function () {
                var clientId = $(this).val();
                loadCalendarProjectCategories(clientId);
                loadCalendarProjectEmployees(clientId);
            });
            $(document).on('change', '#project_client_id', function () {
                var clientId = $(this).val();
                loadCalendarProjectCategories(clientId);
                loadCalendarProjectEmployees(clientId);
            });

            $('#task_sample_form').on('submit', function (event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('tasks.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (var count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#task_sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('.js-example-responsive').val(null).trigger('change');
                        }
                        $('#task_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                });
            });

            $('#event_sample_form').on('submit', function (event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('events.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (let count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#event_sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('.date').datepicker('update');
                        }
                        $('#event_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
            });

            $('#meeting_sample_form').on('submit', function (event) {
                event.preventDefault();
                $.ajax({
                    url: "{{ route('meetings.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (let count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#meeting_sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('.date').datepicker('update');
                        }
                        $('#meeting_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
            });


            $('.dynamic').change(function () {
                if ($(this).val() !== '') {
                    let value = $(this).val();
                    let dependent = $(this).data('dependent');
                    let _token = $('input[name="_token"]').val();
                    $.ajax({
                        url: "{{ route('dynamic_department') }}",
                        method: "POST",
                        data: {value: value, _token: _token, dependent: dependent},
                        success: function (result) {
                            $('select').selectpicker("destroy");
                            $('.department').html(result);
                            $('select').selectpicker();

                        }
                    });
                }
            });
            $('.department').change(function () {
                if ($(this).val() !== '') {
                    let value = $(this).val();
                    let first_name = $(this).data('first_name');
                    let last_name = $(this).data('last_name');
                    let _token = $('input[name="_token"]').val();
                    $.ajax({
                        url: "{{ route('dynamic_employee_department') }}",
                        method: "POST",
                        data: {value: value, _token: _token, first_name: first_name, last_name: last_name},
                        success: function (result) {
                            $('select').selectpicker("destroy");
                            $('.employee').html(result);
                            $('select').selectpicker();

                        }
                    });
                }
            });
            $('.get_employee').change(function () {
                if ($(this).val() !== '') {
                    let value = $(this).val();
                    let first_name = $(this).data('first_name');
                    let last_name = $(this).data('last_name');
                    let _token = $('input[name="_token"]').val();
                    $.ajax({
                        url: "{{ route('dynamic_employee') }}",
                        method: "POST",
                        data: {value: value, _token: _token, first_name: first_name, last_name: last_name},
                        success: function (result) {
                            $('select').selectpicker("destroy");
                            $('.employee').html(result);
                            $('select').selectpicker();
                        }
                    });
                }
            });


            $('.modal').on('hidden.bs.modal', function () {
                $('#travel_sample_form')[0].reset();
                $('#holiday_sample_form')[0].reset();
                $('#training_sample_form')[0].reset();
                $('#project_sample_form')[0].reset();
                $('#leave_sample_form')[0].reset();
                $('#task_sample_form')[0].reset();
                $('#table_data').html('');
                $('select').selectpicker('refresh');
                $('.date').datepicker('update');
            });

            $('.js-example-responsive').select2({
                placeholder: '{{__('Assign Employee...')}}',
                width: 'resolve',
                theme: "classic",
            });


        });

        tinymce.init({
            selector: '.des-editor',
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            },
            height: 130,



            plugins: [
                'advlist autolink lists link image charmap print preview anchor textcolor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table contextmenu paste code wordcount'
            ],
            toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
            branding: false
        });
    })(jQuery);

</script>

@endpush

    <section>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3 mt-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-header with-border">
                                <h3 class="box-title"> {{trans('file.Options')}} </h3>
                            </div>
                            <input type="hidden" id="exact_date" value="">
                            <div class="list-group" id="list_group">
                                @can('store-holiday')
                                    <button class="btn btn-default mb-2 calendar-options text-green" data-record=0 type="button"> <i class="dripicons-brightness-max"></i>  {{trans('file.Holidays')}}
                                    </button>
                                @endcan
                                @can('store-leave')
                                    <button class="btn btn-default mb-2 calendar-options text-aqua " data-record="1" type="button"><i class="dripicons-mail"></i> {{__('Leave Request')}}
                                    </button>
                                @endcan
                                @can('store-travel')
                                    <button class="btn btn-default mb-2 calendar-options text-light-blue" data-record="2" type="button"><i class="fa fa-plane"></i> {{__('Travel Request')}}
                                    </button>
                                @endcan
                                @can('store-training')
                                    <button class="btn btn-default mb-2 calendar-options text-yellow " data-record="3" type="button"><i class="dripicons-trophy"></i>  {{trans('file.Trainings')}}
                                    </button>
                                @endcan
                                @can('store-project')
                                    <button class="btn btn-default mb-2 calendar-options text-purple " data-record="4" type="button"><i class="dripicons-to-do"></i> {{trans('file.Projects')}}
                                    </button>
                                @endcan
                                @can('store-task')
                                    <button class="btn btn-default mb-2 calendar-options text-maroon " data-record="5" type="button"><i class="dripicons-checklist"></i> {{trans('file.Tasks')}}
                                    </button>
                                @endcan
                                @can('store-event')
                                    <button class="btn btn-default mb-2 calendar-options text-navy " data-record="6" type="button"><i class="dripicons-calendar"></i> {{trans('file.Events')}}
                                    </button>
                                @endcan
                                @can('store-meeting')
                                    <button class="btn btn-default mb-2 calendar-options text-teal " data-record="7" type="button"><i class="dripicons-clock"></i> {{trans('file.Meetings')}}
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-9 mt-3">
                    <div class="card">
                        <div class="card-body">
                            <div id='calendar'></div>
                            <div class='container'></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


<div class="modal fade" id="details_model" tabindex="-1" role="dialog" aria-labelledby="basicModal"
     aria-hidden="true"
    >
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel"><span id="model_name"></span> {{trans('file.Info')}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-md-12">

                        <div class="table-responsive">

                            <table id="table_data" class="table  table-bordered">

                            </table>

                        </div>

                    </div>
                </div>


            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{trans('file.Close')}}</button>
        </div>
    </div>
</div>


@include('calendarable.holiday')
@include('calendarable.leave')
@include('calendarable.travel')
@include('calendarable.training')
@include('calendarable.project')
@include('calendarable.task')
@include('calendarable.event')
@include('calendarable.meeting')
