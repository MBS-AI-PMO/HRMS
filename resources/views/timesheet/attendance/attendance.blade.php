@extends('layout.main')
@section('content')

    <section>
        <div class="container-fluid">

            <div class="card">
                <div class="card-body">

                    <div class="card-title text-center"><h3>{{__('Daily Attendances')}}<span id="details_month_year"></span></h3></div>

                    <form method="post" id="filter_form" class="form-horizontal">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="day_month_year">{{__('Select Date')}}</label>
                                    <input class="form-control month_year date" placeholder="{{__('Select Date')}}" readonly=""
                                           id="day_month_year" name="day_month_year" type="text"
                                           value="{{ now()->format(env('Date_Format')) }}">
                                </div>
                            </div>

                            @if (!empty($canUseAttendanceFilters) && $canUseAttendanceFilters)
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>{{ trans('file.Company') }}</label>
                                        <select name="company_id" id="company_id" class="form-control selectpicker"
                                                data-live-search="true" data-live-search-style="contains"
                                                title="{{ __('Selecting', ['key' => trans('file.Company')]) }}...">
                                            @foreach ($companies as $company)
                                                <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>{{ trans('file.Client') }}</label>
                                        <select name="client_id" id="client_id" class="selectpicker form-control"
                                                data-live-search="true" data-live-search-style="contains"
                                                title="{{ __('Selecting', ['key' => trans('file.Client')]) }}...">
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>{{ trans('file.Location') }}</label>
                                        <select name="location_id" id="location_id" class="selectpicker form-control"
                                                data-live-search="true" data-live-search-style="contains"
                                                title="{{ __('Selecting', ['key' => trans('file.Location')]) }}...">
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>{{ trans('file.Employee') }}</label>
                                        <select name="employee_id" id="employee_id" class="selectpicker form-control"
                                                data-live-search="true" data-live-search-style="contains"
                                                title="{{ __('Selecting', ['key' => trans('file.Employee')]) }}...">
                                        </select>
                                    </div>
                                </div>
                            @else
                                <input type="hidden" name="employee_id" id="employee_id" value="{{ Auth::user()->id }}">
                            @endif

                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="filtering btn btn-primary btn-block">
                                        <i class="fa fa-search"></i> {{ trans('file.Search') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="daily_attendance-table" class="table ">
                <thead>
                <tr>
                    <th>{{trans('file.Employee')}}</th>
                    <th>{{trans('file.Company')}}</th>
                    <th>{{trans('file.Date')}}</th>
                    <th>{{trans('file.status')}}</th>
                    <th>{{__('Clock In')}}</th>
                    <th>{{__('Clock Out')}}</th>
                    <th>{{trans('file.Late')}}</th>
                    <th>{{__('Early Leaving')}}</th>
                    <th>{{trans('file.Overtime')}}</th>
                    <th>{{__('Total Work')}}</th>
                    <th>{{__('Total Rest')}}</th>
                </tr>
                </thead>
            </table>
        </div>
    </section>


@endsection

@push('scripts')
<script type="text/javascript">
    (function($) {
        "use strict";

        $(document).ready(function () {

            let date = $('.date');
            date.datepicker({
                format: '{{ env('Date_Format_JS')}}',
                autoclose: true,
                todayHighlight: true,
                endDate: new Date()
            });

            fill_datatable($('#day_month_year').val());

            function fill_datatable(filter_month_year = '', company_id = '', client_id = '', location_id = '', employee_id = '') {

                let table_table = $('#daily_attendance-table').DataTable({
                initComplete: function () {
                    this.api().columns([2, 4]).every(function () {
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
                        url: "{{ route('attendances.index') }}",
                        data: {
                            filter_month_year: filter_month_year,
                            company_id: company_id,
                            client_id: client_id,
                            location_id: location_id,
                            employee_id: employee_id,
                            "_token": "{{ csrf_token()}}"
                        }
                    },

                    columns: [
                        {
                            data: 'employee_name',
                            name: 'employee_name'
                        },
                        {
                            data: 'company',
                            name: 'company'
                        },
                        {
                            data: 'attendance_date',
                            name: 'attendance_date',
                        },
                        {
                            data: 'attendance_status',
                            name: 'attendance_status'
                        },
                        {
                            data: 'clock_in',
                            name: 'clock_in',
                        },
                        {
                            data: 'clock_out',
                            name: 'clock_out',
                        },
                        {
                            data: 'time_late',
                            name: 'time_late',
                        },
                        {
                            data: 'early_leaving',
                            name: 'early_leaving',
                        },
                        {
                            data: 'overtime',
                            name: 'overtime',
                        },
                        {
                            data: 'total_work',
                            name: 'total_work'
                        },
                        {
                            data: 'total_rest',
                            name: 'total_rest'
                        },
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
                            'targets': [0, 10],
                        },
                    ],

                    'select': {style: 'multi', selector: 'td:first-child'},
                    'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    dom: '<"row"lfB>rtip',
                    buttons: [
                        {
                            extend: 'pdf',
                            text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                            exportOptions: {
                                columns: ':visible:Not(.not-exported)',
                                rows: ':visible'
                            },
                        },
                        {
                            extend: 'csv',
                            text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                            exportOptions: {
                                columns: ':visible:Not(.not-exported)',
                                rows: ':visible'
                            },
                        },
                        {
                            extend: 'print',
                            text: '<i title="print" class="fa fa-print"></i>',
                            exportOptions: {
                                columns: ':visible:Not(.not-exported)',
                                rows: ':visible'
                            },
                        },
                        {
                            extend: 'colvis',
                            text: '<i title="column visibility" class="fa fa-eye"></i>',
                            columns: ':gt(0)'
                        },
                    ],
                });

                new $.fn.dataTable.FixedHeader(table_table);
            }

            function resetSelect($select, allLabel) {
                $select.selectpicker('destroy');
                $select.html('<option value="">' + allLabel + '</option>');
                $select.selectpicker();
            }

            function loadClients(companyId) {
                resetSelect($('#client_id'), @json(__('All')));

                if (!companyId) {
                    return $.Deferred().resolve().promise();
                }

                return $.post("{{ route('dynamic_clients') }}", {
                    value: companyId,
                    _token: '{{ csrf_token() }}'
                }).done(function(result) {
                    $('#client_id').selectpicker('destroy');
                    $('#client_id').html('<option value="">' + @json(__('All')) + '</option>' + result);
                    $('#client_id').selectpicker();
                });
            }

            function loadLocations(companyId, clientId) {
                resetSelect($('#location_id'), @json(__('All')));

                if (!companyId && !clientId) {
                    return $.Deferred().resolve().promise();
                }

                return $.post("{{ route('dynamic_locations') }}", {
                    company_id: companyId || '',
                    client_id: clientId || '',
                    _token: '{{ csrf_token() }}'
                }).done(function(result) {
                    $('#location_id').selectpicker('destroy');
                    $('#location_id').html('<option value="">' + @json(__('All')) + '</option>' + result);
                    $('#location_id').selectpicker();
                });
            }

            function loadEmployees(companyId, clientId, locationId) {
                resetSelect($('#employee_id'), @json(__('All')));

                if (!companyId && !clientId) {
                    return $.Deferred().resolve().promise();
                }

                return $.post("{{ route('dynamic_employee') }}", {
                    value: companyId || '',
                    client_id: clientId || '',
                    location_id: locationId || '',
                    first_name: 'first_name',
                    last_name: 'last_name',
                    _token: '{{ csrf_token() }}'
                }).done(function(result) {
                    $('#employee_id').selectpicker('destroy');
                    $('#employee_id').html('<option value="">' + @json(__('All')) + '</option>' + result);
                    $('#employee_id').selectpicker();
                });
            }

            $('#company_id').on('changed.bs.select', function() {
                var companyId = $(this).val();
                loadClients(companyId).always(function() {
                    loadLocations(companyId, '').always(function() {
                        loadEmployees(companyId, '', '');
                    });
                });
            });

            $('#client_id').on('changed.bs.select', function() {
                var companyId = $('#company_id').val();
                var clientId = $(this).val();
                loadLocations(companyId, clientId).always(function() {
                    loadEmployees(companyId, clientId, $('#location_id').val());
                });
            });

            $('#location_id').on('changed.bs.select', function() {
                loadEmployees($('#company_id').val(), $('#client_id').val(), $(this).val());
            });

            $('#filter_form').on('submit', function (e) {
                e.preventDefault();
                var filter_month_year = $('#day_month_year').val();
                var company_id = $('#company_id').val() || '';
                var client_id = $('#client_id').val() || '';
                var location_id = $('#location_id').val() || '';
                var employee_id = $('#employee_id').val() || '';

                if (filter_month_year !== '') {
                    if ($.fn.DataTable.isDataTable('#daily_attendance-table')) {
                        $('#daily_attendance-table').DataTable().destroy();
                    }
                    fill_datatable(filter_month_year, company_id, client_id, location_id, employee_id);
                } else {
                    alert(@json(__('Select date to search')));
                }
            });
        });
    })(jQuery);
</script>
@endpush
