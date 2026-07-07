@extends('layout.main')
@section('content')

    <section>
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-header with-border">
                    <h3 class="card-title text-center">{{__('Monthly Attendance Info')}} <hr><span
                                        id="details_month_year" class="thin-text"></span></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <form method="post" id="filter_form" class="form-horizontal">
                                @csrf
                                <div class="row">

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input class="form-control date"  name="month_year" type="text" id="month_year">
                                        </div>
                                    </div>

                                    {{-- if (Au@th::user()->role_users_id==1) --}}
                                    @if (!empty($canUseAttendanceFilters) && $canUseAttendanceFilters)
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <select name="company_id" id="company_id" class="form-control selectpicker dynamic"
                                                        data-live-search="true" data-live-search-style="contains"  data-first_name="first_name" data-last_name="last_name"
                                                        title='{{__('Selecting',['key'=>trans('file.Company')])}}...'>
                                                    @foreach($companies as $company)
                                                        <option value="{{$company->id}}">{{$company->company_name}}</option>
                                                    @endforeach

                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <select name="client_id" id="client_id" class="selectpicker form-control"
                                                        data-live-search="true" data-live-search-style="contains"
                                                        title='{{__('Selecting',['key'=>trans('file.Client')])}}...'>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <select name="location_id" id="location_id" class="selectpicker form-control"
                                                        data-live-search="true" data-live-search-style="contains"
                                                        title='{{__('Selecting',['key'=>trans('file.Location')])}}...'>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <select name="employee_id" id="employee_id"   class="selectpicker form-control"
                                                        data-live-search="true" data-live-search-style="begins"
                                                        title='{{__('Selecting',['key'=>trans('file.Employee')])}}...'>
                                                </select>
                                            </div>
                                        </div>
                                    @else
                                        <input type="hidden" name="employee_id" id="employee_id" value="{{ Auth::user()->id }}">
                                    @endif

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <select name="report_mode" id="report_mode" class="form-control selectpicker">
                                                <option value="cumulative" selected>{{ __('Cumulative') }}</option>
                                                <option value="non_cumulative">{{ __('Non-Cumulative') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <button name="submit_form" id="submit_form" type="submit" class="btn btn-primary"><i class="fa fa fa-check-square-o"></i> {{trans('file.Get')}}</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <span class="attendace_mark_info mb-3 d-block">
                <small>
                    <strong>P</strong> = {{ __('Present') }} |
                    <strong>A</strong> = {{ __('Absent') }} ({{ __('Uninformed / unapproved leave') }}) |
                    <strong>CL</strong> = {{ __('Casual Leave') }} |
                    <strong>SL</strong> = {{ __('Sick Leave') }} |
                    <strong>ML</strong> = {{ __('Maternity Leave') }} |
                    <strong>SPL</strong> = {{ __('Special Leave') }} |
                    <strong>WFH</strong> = {{ __('Work From Home') }} |
                    <strong>LT</strong> = {{ __('Late') }} |
                    <strong>HD</strong> = {{ __('Half Day') }} |
                    <strong>EL</strong> = {{ __('Early Leave') }} |
                    <strong>OFF</strong> = {{ __('Public Holidays / Weekends') }}
                </small>
            </span>
        </div>
        <div class="table-responsive">
            <table id="month_wise_attendance-table" class="table ">
                <thead>
                <tr>
                    <th></th>
                    <th>{{ trans('file.Employee') }}</th>
                    <th>{{ trans('file.Department') }}</th>
                    <th>{{ trans('file.Designation') }}</th>
                    <th>{{ __('CNIC') }}</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>{{__('Worked Days')}}</th>
                    <th>{{__('Total Worked Hours')}}</th>
                    <th>{{ __('P') }}</th>
                    <th>{{ __('A') }}</th>
                    <th>{{ __('CL') }}</th>
                    <th>{{ __('SL') }}</th>
                    <th>{{ __('ML') }}</th>
                    <th>{{ __('SPL') }}</th>
                    <th>{{ __('WFH') }}</th>
                    <th>{{ __('LT') }}</th>
                    <th>{{ __('HD') }}</th>
                    <th>{{ __('EL') }}</th>
                    <th>{{ __('OFF') }}</th>
                </tr>

                </thead>
            </table>
        </div>
    </section>



@endsection

@push('styles')
<style>
    #month_wise_attendance-table {
        font-size: 12px;
    }

    #month_wise_attendance-table th,
    #month_wise_attendance-table td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 4px 6px;
    }

    #month_wise_attendance-table.monthly-mode-cumulative th:nth-child(n+6):not(:nth-last-child(-n+13)),
    #month_wise_attendance-table.monthly-mode-cumulative td:nth-child(n+6):not(:nth-last-child(-n+13)) {
        text-align: center;
        min-width: 34px;
        max-width: 42px;
        padding: 4px 2px;
    }

    #month_wise_attendance-table.monthly-mode-non-cumulative th:nth-child(n+6):not(:nth-last-child(-n+2)),
    #month_wise_attendance-table.monthly-mode-non-cumulative td:nth-child(n+6):not(:nth-last-child(-n+2)) {
        text-align: center;
        min-width: 34px;
        max-width: 42px;
        padding: 4px 2px;
    }

    @media print {
        @page {
            size: A2 landscape;
            margin: 6mm;
        }

        html, body {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #month_wise_attendance-table {
            font-size: 7px !important;
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
        }

        #month_wise_attendance-table th,
        #month_wise_attendance-table td {
            padding: 1px 2px !important;
            white-space: nowrap !important;
        }
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript">

    (function($) {
        "use strict";

        var SUMMARY_BASE_START_INDEX = 36;
        var SUMMARY_COUNTS_START_INDEX = 38;
        var SUMMARY_COUNTS_END_INDEX = 48;

        function isCumulativeMode() {
            return ($('#report_mode').val() || 'cumulative') === 'cumulative';
        }

        function applyTableModeClass() {
            var $table = $('#month_wise_attendance-table');
            if (isCumulativeMode()) {
                $table.removeClass('monthly-mode-non-cumulative').addClass('monthly-mode-cumulative');
            } else {
                $table.removeClass('monthly-mode-cumulative').addClass('monthly-mode-non-cumulative');
            }
        }

        function applyReportModeVisibility(tableApi) {
            var showCounts = isCumulativeMode();
            for (var s = SUMMARY_COUNTS_START_INDEX; s <= SUMMARY_COUNTS_END_INDEX; s++) {
                tableApi.column(s).visible(showCounts);
            }
            applyTableModeClass();
        }

        function revealMonthlyExportColumns(tableApi, includeCounts) {
            for (var i = 0; i < 31; i++) {
                tableApi.column(i + 5).visible(true);
            }

            tableApi.column(SUMMARY_BASE_START_INDEX).visible(true);
            tableApi.column(SUMMARY_BASE_START_INDEX + 1).visible(true);
            if (includeCounts) {
                for (var s = SUMMARY_COUNTS_START_INDEX; s <= SUMMARY_COUNTS_END_INDEX; s++) {
                    tableApi.column(s).visible(true);
                }
            }
        }

        function monthlyAttendanceExportOptions(includeCounts) {
            var columns = [1, 2, 3, 4];

            for (var i = 5; i <= 35; i++) {
                columns.push(i);
            }

            columns.push(SUMMARY_BASE_START_INDEX, SUMMARY_BASE_START_INDEX + 1);
            if (includeCounts) {
                for (var s = SUMMARY_COUNTS_START_INDEX; s <= SUMMARY_COUNTS_END_INDEX; s++) {
                    columns.push(s);
                }
            }

            return {
                columns: columns,
                modifier: {
                    page: 'all',
                    search: 'none'
                }
            };
        }

        function getPdfContentWidth(doc) {
            var sizes = {
                A2: [1190.55, 1683.78],
                A3: [841.89, 1190.55],
                A4: [595.28, 841.89],
                LEGAL: [612, 1008]
            };
            var size = sizes[doc.pageSize] || sizes.A2;
            var pageWidth = doc.pageOrientation === 'landscape' ? size[1] : size[0];
            var margins = (doc.pageMargins[0] || 0) + (doc.pageMargins[2] || 0);

            return pageWidth - margins;
        }

        function customizeMonthlyPdf(doc) {
            doc.pageOrientation = 'landscape';
            doc.pageSize = 'A2';
            doc.defaultStyle.fontSize = 6;
            doc.styles.tableHeader.fontSize = 6;
            doc.styles.tableHeader.fillColor = '#2c3e50';
            doc.pageMargins = [6, 12, 6, 12];

            var contentWidth = getPdfContentWidth(doc);

            doc.content.forEach(function (item) {
                if (!item.table || !item.table.body || !item.table.body[0]) {
                    return;
                }

                var colCount = item.table.body[0].length;
                var dayStart = 4;
                var summaryColumnCount = colCount > 38 ? 13 : 2;
                var dayEnd = colCount - (summaryColumnCount + 1);
                var dayCount = Math.max(dayEnd - dayStart + 1, 1);

                var employeeWidth = Math.min(95, contentWidth * 0.11);
                var deptWidth = Math.min(72, contentWidth * 0.08);
                var designationWidth = Math.min(72, contentWidth * 0.08);
                var cnicWidth = Math.min(78, contentWidth * 0.09);
                var summaryWidth = summaryColumnCount > 2
                    ? Math.min(32, contentWidth * 0.03)
                    : Math.min(48, contentWidth * 0.05);

                var fixedWidth = employeeWidth + deptWidth + designationWidth + cnicWidth + (summaryWidth * summaryColumnCount);
                var dayWidth = Math.max(10, (contentWidth - fixedWidth) / dayCount);

                item.table.widths = [];
                for (var c = 0; c < colCount; c++) {
                    if (c === 0) {
                        item.table.widths.push(employeeWidth);
                    } else if (c === 1) {
                        item.table.widths.push(deptWidth);
                    } else if (c === 2) {
                        item.table.widths.push(designationWidth);
                    } else if (c === 3) {
                        item.table.widths.push(cnicWidth);
                    } else if (c >= colCount - summaryColumnCount) {
                        item.table.widths.push(summaryWidth);
                    } else {
                        item.table.widths.push(dayWidth);
                    }
                }

                item.layout = {
                    hLineWidth: function () { return 0.4; },
                    vLineWidth: function () { return 0.4; },
                    hLineColor: function () { return '#cccccc'; },
                    vLineColor: function () { return '#cccccc'; },
                    paddingLeft: function () { return 2; },
                    paddingRight: function () { return 2; },
                    paddingTop: function () { return 2; },
                    paddingBottom: function () { return 2; }
                };
            });
        }

        function customizeMonthlyPrint(win) {
            var monthLabel = $('#details_month_year').text() || $('#month_year').val();
            var doc = win.document;
            var $body = $(doc.body);
            var summarySelector = isCumulativeMode() ? '-n+13' : '-n+2';

            $body.css({
                margin: '0',
                padding: '0',
                width: '100%'
            });

            $(doc.head).append(
                '<style>' +
                '@page { size: A2 landscape; margin: 6mm; }' +
                'html, body { width: 100%; margin: 0; padding: 0; }' +
                'table { width: 100% !important; max-width: 100% !important; table-layout: fixed !important; border-collapse: collapse !important; font-size: 7px !important; }' +
                'table th, table td { padding: 1px 2px !important; white-space: nowrap !important; border: 1px solid #ccc !important; text-align: center; }' +
                'table th:nth-child(-n+4), table td:nth-child(-n+4) { text-align: left; }' +
                'table th:nth-child(1), table td:nth-child(1) { width: 11%; }' +
                'table th:nth-child(2), table td:nth-child(2), table th:nth-child(3), table td:nth-child(3) { width: 8%; }' +
                'table th:nth-child(4), table td:nth-child(4) { width: 9%; }' +
                'table th:nth-last-child(' + summarySelector + '), table td:nth-last-child(' + summarySelector + ') { width: 3%; }' +
                '</style>'
            );

            $body.prepend(
                '<div style="margin-bottom:8px;width:100%;">' +
                '<h3 style="margin:0 0 4px;font-size:14px;">{{ __('Monthly Attendance Info') }} - ' + monthLabel + '</h3>' +
                '<div style="font-size:7px;">P=Present, A=Absent, CL=Casual Leave, SL=Sick Leave, ML=Maternity Leave, SPL=Special Leave, WFH=Work From Home, LT=Late, HD=Half Day, EL=Early Leave, OFF=Holiday/Weekend</div>' +
                '</div>'
            );

            $body.find('table').attr('width', '100%').css({
                width: '100%',
                'max-width': '100%',
                'table-layout': 'fixed'
            });
        }

        $(document).ready(function() {
            applyTableModeClass();

            let date = $('.date');
            date.datepicker({
                format: "MM yyyy",
                startView: "months",
                minViewMode: 1,
                autoclose: true,
            }).datepicker("setDate", new Date());

            var initialEmployee = $('#employee_id').val() || '';
            if (initialEmployee) {
                fill_datatable('', '', '', initialEmployee);
            }

            function fill_datatable(filter_company = '', filter_client = '', filter_location = '', filter_employee = $('#employee_id').val() || '', filter_month_year = $('#month_year').val()) {
                $('#details_month_year').html($('#month_year').val());
                let table_table = $('#month_wise_attendance-table').DataTable({
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
                    responsive: false,
                    scrollX: true,
                    fixedHeader: {
                        header: true,
                        footer: true
                    },
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "{{ route('monthly_attendances.index') }}",
                        type: 'GET',
                        timeout: 120000,
                        data: function (d) {
                            d.filter_company = filter_company;
                            d.filter_client = filter_client;
                            d.filter_location = filter_location;
                            d.filter_employee = filter_employee;
                            d.filter_month_year = filter_month_year;
                        },
                        dataSrc: function (json) {
                            if (json.error) {
                                alert(json.error);
                                return [];
                            }

                            if (json.date_range && json.date_range.length) {
                                $.each(json.date_range, function (key, value) {
                                    $(table_table.column(key + 5).header()).text(value);
                                });
                                for (var i = json.date_range.length; i < 31; i++) {
                                    table_table.column(i + 5).visible(false);
                                }
                            }

                            $(table_table.column(36).header()).text(@json(__('Worked Days')));
                            $(table_table.column(37).header()).text(@json(__('Total Worked Hours')));
                            table_table.column(36).visible(true);
                            table_table.column(37).visible(true);
                            $(table_table.column(38).header()).text('P');
                            $(table_table.column(39).header()).text('A');
                            $(table_table.column(40).header()).text('CL');
                            $(table_table.column(41).header()).text('SL');
                            $(table_table.column(42).header()).text('ML');
                            $(table_table.column(43).header()).text('SPL');
                            $(table_table.column(44).header()).text('WFH');
                            $(table_table.column(45).header()).text('LT');
                            $(table_table.column(46).header()).text('HD');
                            $(table_table.column(47).header()).text('EL');
                            $(table_table.column(48).header()).text('OFF');
                            for (var s = 38; s <= 48; s++) {
                                table_table.column(s).visible(true);
                            }
                            applyReportModeVisibility(table_table);

                            return json.data || [];
                        },
                        error: function (xhr) {
                            console.error('Monthly attendance load failed', xhr.status, xhr.responseText);
                            var message = '{{ __('Failed to load monthly attendance. Please apply filters and try again.') }}';
                            try {
                                var payload = JSON.parse(xhr.responseText);
                                if (payload.error) {
                                    message = payload.error;
                                }
                            } catch (e) {}
                            alert(message);
                        }
                    },

                    columns: [
                        {
                            data: null,
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'employee_name',
                            name: 'employee_name',
                        },
                        {
                            data: 'department_name',
                            name: 'department_name',
                        },
                        {
                            data: 'designation_name',
                            name: 'designation_name',
                        },
                        {
                            data: 'cnic',
                            name: 'cnic',
                        },
                        {
                            data: 'day1',
                            name: 'day1',
                        },
                        {
                            data: 'day2',
                            name: 'day2',
                        },
                        {
                            data: 'day3',
                            name: 'day3',
                        },
                        {
                            data: 'day4',
                            name: 'day4',
                        },
                        {
                            data: 'day5',
                            name: 'day5',
                        },
                        {
                            data: 'day6',
                            name: 'day6',
                        },
                        {
                            data: 'day7',
                            name: 'day7',
                        },
                        {
                            data: 'day8',
                            name: 'day8',
                        },
                        {
                            data: 'day9',
                            name: 'day9',
                        },
                        {
                            data: 'day10',
                            name: 'day10',
                        },
                        {
                            data: 'day11',
                            name: 'day11',
                        },
                        {
                            data: 'day12',
                            name: 'day12',
                        },
                        {
                            data: 'day13',
                            name: 'day13',
                        },
                        {
                            data: 'day14',
                            name: 'day14',
                        },
                        {
                            data: 'day15',
                            name: 'day15',
                        },
                        {
                            data: 'day16',
                            name: 'day16',
                        },
                        {
                            data: 'day17',
                            name: 'day17',
                        },
                        {
                            data: 'day18',
                            name: 'day18',
                        },
                        {
                            data: 'day19',
                            name: 'day19',
                        },
                        {
                            data: 'day20',
                            name: 'day20',
                        },
                        {
                            data: 'day21',
                            name: 'day21',
                        },
                        {
                            data: 'day22',
                            name: 'day22',
                        },
                        {
                            data: 'day23',
                            name: 'day23',
                        },
                        {
                            data: 'day24',
                            name: 'day24',
                        },
                        {
                            data: 'day25',
                            name: 'day25',
                        },
                        {
                            data: 'day26',
                            name: 'day26',
                        },
                        {
                            data: 'day27',
                            name: 'day27',
                        },
                        {
                            data: 'day28',
                            name: 'day28',
                        },
                        {
                            data: 'day29',
                            name: 'day29',
                        },
                        {
                            data: 'day30',
                            name: 'day30',
                        },
                        {
                            data: 'day31',
                            name: 'day31',
                        },
                        {
                            data: 'worked_days',
                            name: 'worked_days',
                            title: @json(__('Worked Days')),
                        },
                        {
                            data: 'total_worked_hours',
                            name: 'total_worked_hours',
                            title: @json(__('Total Worked Hours')),
                        },
                        {
                            data: 'count_p',
                            name: 'count_p',
                        },
                        {
                            data: 'count_a',
                            name: 'count_a',
                        },
                        {
                            data: 'count_cl',
                            name: 'count_cl',
                        },
                        {
                            data: 'count_sl',
                            name: 'count_sl',
                        },
                        {
                            data: 'count_ml',
                            name: 'count_ml',
                        },
                        {
                            data: 'count_spl',
                            name: 'count_spl',
                        },
                        {
                            data: 'count_wfh',
                            name: 'count_wfh',
                        },
                        {
                            data: 'count_lt',
                            name: 'count_lt',
                        },
                        {
                            data: 'count_hd',
                            name: 'count_hd',
                        },
                        {
                            data: 'count_el',
                            name: 'count_el',
                        },
                        {
                            data: 'count_off',
                            name: 'count_off',
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
                            'className': 'not-exported',
                            'render': function (data, type, row, meta) {
                                if (type == 'display') {
                                    data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                                }

                                return data;
                            },
                            'checkboxes': {
                                'selectRow': true,
                                'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                            },
                            'targets': [0]
                        },
                    ],

                    'select': {style: 'multi', selector: 'td:first-child'},
                    'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    dom: '<"row"lfB>rtip',
                    buttons: [
                        {
                            extend: 'pdfHtml5',
                            orientation: 'landscape',
                            pageSize: 'A2',
                            title: '{{ __('Monthly Attendance Info') }}',
                            messageTop: function () {
                                return ($('#details_month_year').text() || $('#month_year').val()) +
                                    ' | P=Present, A=Absent, CL, SL, ML, SPL, WFH, LT, HD, EL, OFF';
                            },
                            text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                            action: function (e, dt, button, config) {
                                var includeCounts = isCumulativeMode();
                                revealMonthlyExportColumns(dt, includeCounts);
                                config.exportOptions = monthlyAttendanceExportOptions(includeCounts);
                                $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                            },
                            customize: customizeMonthlyPdf
                        },
                        {
                            extend: 'csvHtml5',
                            text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                            action: function (e, dt, button, config) {
                                var includeCounts = isCumulativeMode();
                                revealMonthlyExportColumns(dt, includeCounts);
                                config.exportOptions = monthlyAttendanceExportOptions(includeCounts);
                                $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                            }
                        },
                        {
                            extend: 'print',
                            title: '',
                            text: '<i title="print" class="fa fa-print"></i>',
                            action: function (e, dt, button, config) {
                                var includeCounts = isCumulativeMode();
                                revealMonthlyExportColumns(dt, includeCounts);
                                config.exportOptions = monthlyAttendanceExportOptions(includeCounts);
                                $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                            },
                            customize: customizeMonthlyPrint
                        },
                    ],
                });
            }

            $('#submit_form').on('click', function (e) {
                e.preventDefault();

                var filter_company = $('#company_id').selectpicker('val') || $('#company_id').val();
                var filter_client = $('#client_id').selectpicker('val') || $('#client_id').val();
                var filter_location = $('#location_id').selectpicker('val') || $('#location_id').val();
                var filter_employee = $('#employee_id').selectpicker('val') || $('#employee_id').val();
                var filter_month_year = $('#month_year').val();
                if (filter_month_year !== '' && (filter_company !== '' || filter_client !== '' || filter_employee !== '')) {
                    if ($.fn.DataTable.isDataTable('#month_wise_attendance-table')) {
                        $('#month_wise_attendance-table').DataTable().destroy();
                    }
                    fill_datatable(filter_company, filter_client, filter_location, filter_employee, filter_month_year);
                }
                else {
                    alert('{{__('Select at least one filter option')}}');
                }
            });

            $('#report_mode').on('changed.bs.select change', function () {
                applyTableModeClass();
                if ($.fn.DataTable.isDataTable('#month_wise_attendance-table')) {
                    applyReportModeVisibility($('#month_wise_attendance-table').DataTable());
                }
            });

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
                    return;
                }

                $.post("{{ route('dynamic_employee') }}", {
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
        });


    })(jQuery);
</script>
@endpush
