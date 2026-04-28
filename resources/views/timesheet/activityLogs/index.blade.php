@extends('layout.main')
@section('content')

    <section>
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-header with-border">
                    <h3 class="card-title">{{__('Employee Activity Logs')}}</h3>
                </div>
                <div class="card-body">
                    <form method="post" id="activity_log_filter_form" class="form-horizontal">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input class="form-control date" placeholder="{{__('Select Date')}}" readonly id="activity_date" name="activity_date" type="text">
                                </div>
                            </div>

                            @if ((Auth::user()->can('daily-attendances')))
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <select name="company_id" id="company_id" class="form-control selectpicker dynamic"
                                                data-live-search="true" data-live-search-style="contains" data-first_name="first_name" data-last_name="last_name"
                                                title='{{__('Selecting',['key'=>trans('file.Company')])}}...'>
                                            @foreach($companies as $company)
                                                <option value="{{$company->id}}">{{$company->company_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <select name="employee_id" id="employee_id" class="selectpicker form-control"
                                                data-live-search="true" data-live-search-style="contains"
                                                title='{{__('Selecting',['key'=>trans('file.Employee')])}}...'>
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-3">
                                <div class="form-group">
                                    <button name="submit_form" id="submit_form" type="submit" class="btn btn-primary">
                                        <i class="fa fa-check-square-o"></i> {{trans('file.Get')}}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="employee-activity-log-table" class="table ">
                <thead>
                <tr>
                    <th>{{trans('file.Employee')}}</th>
                    <th>{{__('Action')}}</th>
                    <th>{{__('Description')}}</th>
                    <th>{{__('Performed By')}}</th>
                    <th>{{__('IP Address')}}</th>
                    <th>{{__('Date Time')}}</th>
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
            $('.date').datepicker({
                format: '{{ env('Date_Format_JS')}}',
                autoclose: true,
                todayHighlight: true,
                endDate: new Date()
            });
        });

        fill_datatable();

        function fill_datatable(activity_date = '', company_id = '', employee_id = '') {
            let table_table = $('#employee-activity-log-table').DataTable({
                responsive: true,
                fixedHeader: {
                    header: true,
                    footer: true
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('employee_activity_logs.index') }}",
                    data: {
                        activity_date: activity_date,
                        company_id: company_id,
                        employee_id: employee_id,
                        "_token": "{{ csrf_token()}}",
                    }
                },
                columns: [
                    { data: 'employee_name', name: 'employee_name' },
                    { data: 'action', name: 'action' },
                    { data: 'description', name: 'description' },
                    { data: 'performed_by', name: 'performed_by' },
                    { data: 'ip_address', name: 'ip_address' },
                    { data: 'created_at', name: 'created_at' },
                ],
                order: [],
                language: {
                    lengthMenu: '_MENU_ {{__("records per page")}}',
                    info: '{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)',
                    search: '{{trans("file.Search")}}',
                    paginate: {
                        previous: '{{trans("file.Previous")}}',
                        next: '{{trans("file.Next")}}'
                    }
                },
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
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
                ],
            });
            new $.fn.dataTable.FixedHeader(table_table);
        }

        $('#activity_log_filter_form').on('submit', function (e) {
            e.preventDefault();
            let activity_date = $('#activity_date').val();
            let company_id = $('#company_id').val();
            let employee_id = $('#employee_id').val();
            $('#employee-activity-log-table').DataTable().destroy();
            fill_datatable(activity_date, company_id, employee_id);
        });

        $('.dynamic').change(function () {
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
                        $('#employee_id').html(result);
                        $('select').selectpicker();
                    }
                });
            }
        });
    })(jQuery);
</script>
@endpush
