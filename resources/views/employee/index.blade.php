@extends('layout.main')
@section('content')


    <section>

        <div class="container-fluid">
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (!empty($filterLocationId) && !empty($filterLocationName))
                <div class="alert alert-info mb-0">
                    {{ __('Showing employees for location: :location', ['location' => $filterLocationName]) }}
                </div>
            @endif
            <span id="general_result"></span>
            
        </div>


        <div class="container-fluid mb-3">
            @empty($teamLeaderViewOnly)
                @can('store-details-employee')
                    <button type="button" class="btn btn-info" name="create_record" id="create_record"><i
                                class="fa fa-plus"></i> {{__('Add Employee')}}</button>
                @endcan
                @can('modify-details-employee')
                    <button type="button" class="btn btn-danger" name="bulk_delete" id="bulk_delete"><i
                                class="fa fa-minus-circle"></i> {{__('Bulk delete')}}</button>
                @endcan
            @endempty
            <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                <i class="fa fa-filter" aria-hidden="true"></i> Filter
            </button>
        </div>
        <div class="col-12">
            <!-- Filtering -->
            <div class="collapse" id="collapseExample">
                <div class="card card-body">
                    <form action="" method="GET" id="filter_form">
                        <div class="row">
                            <!-- Client -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-bold"><strong>{{ trans('file.Client') }}</strong></label>
                                    <select name="client_id" id="client_id_filter"
                                            class="form-control selectpicker"
                                            data-live-search="true" data-live-search-style="contains"
                                            title="{{ __('Selecting', ['key' => trans('file.Client')]) }}...">
                                        <option value=""></option>
                                        @foreach ($clients as $client)
                                            @php
                                                $clientName = trim(($client->first_name ?? '').' '.($client->last_name ?? ''));
                                                $clientCompany = trim((string) ($client->company_name ?? ''));
                                                $clientLabel = ($clientName !== '' && $clientCompany !== '')
                                                    ? $clientName.' — '.$clientCompany
                                                    : ($clientName !== '' ? $clientName : $clientCompany);
                                            @endphp
                                            <option value="{{ $client->id }}">{{ $clientLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <!--/ Client -->

                            <!-- Company -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-bold"><strong>{{trans('file.Company')}}</strong></label>
                                    <select name="company_id" id="company_id_filter"
                                            class="form-control selectpicker"
                                            data-live-search="true" data-live-search-style="contains"
                                            data-shift_name="shift_name" data-dependent="department_name"
                                            title="{{__('Selecting',['key'=>trans('file.Company')])}}...">
                                            <option value=""></option>
                                        @foreach($companies as $company)
                                            <option value="{{$company->id}}">{{$company->company_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <!--/ Company-->

                            <!-- Department-->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-bold"><b>{{trans('file.Department')}}</b></label>
                                    <select name="department_id" id="department_id_filter"
                                            class="selectpicker form-control designationFilter"
                                            data-live-search="true" data-live-search-style="contains"
                                            data-designation_name="designation_name"
                                            title="{{__('Selecting',['key'=>trans('file.Department')])}}...">
                                    </select>
                                </div>
                            </div>
                            <!--/ Department-->

                            <!-- Designation -->
                            <div class="col-md-3 form-group">
                                <label class="text-bold"><b>{{trans('file.Designation')}}</b></label>
                                <select name="designation_id" id="designation_id_filter" class="selectpicker form-control"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{__('Selecting',['key'=>trans('file.Designation')])}}...">
                                </select>
                            </div>
                            <!--/ Designation -->
                        </div>
                        <div class="row">
                            <!-- Office Shift -->
                            <div class="col-md-3 form-group">
                                <label class="text-bold"><b>{{__('Office Shift')}}</b></label>
                                <select name="office_shift_id" id="office_shift_id_filter" class="selectpicker form-control"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{__('Selecting Office Shift')}}...">
                                </select>
                            </div>
                            <!--/ Office Shift -->

                            <div class="col-md-2">
                                <label class="text-bold"></label><br>
                                <button type="button" class="btn btn-dark" id="filterSubmit">
                                    <i class="fa fa-arrow-right" aria-hidden="true"></i> &nbsp; GET
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!--/ Filtering -->
        </div>


        <div class="table-responsive">
            <table id="employee-table" class="table ">
                <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Employee')}}</th>
                    <th>{{trans('file.Company')}}</th>
                    <th>{{trans('file.Contact')}}</th>
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
                </thead>

            </table>
        </div>
    </section>



    <div id="formModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header py-2">
                    <h5 id="exampleModalLabel" class="modal-title mb-0">{{__('Add Employee')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body employee-add-modal-body">
                    <span id="form_result"></span>
                    <form method="post" id="sample_form" class="form-horizontal employee-add-form" enctype="multipart/form-data" data-hrms-no-refresh>

                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{__('First Name')}} <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name" placeholder="{{__('First Name')}}"
                                       required class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{__('Last Name')}} <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name" placeholder="{{__('Last Name')}}"
                                       required class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{__('CNIC')}} <span class="text-danger">*</span></label>
                                <input type="text" name="cnic" id="cnic" class="form-control form-control-sm cnic-input"
                                       placeholder="35201-1234567-1" maxlength="15" autocomplete="off"
                                       inputmode="numeric" required title="{{ __('Format: 12345-1234567-1') }}">
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{trans('file.Email')}}</label>
                                <input type="email" name="email" id="email" placeholder="example@example.com"
                                       class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{trans('file.Phone')}} <span class="text-danger">*</span></label>
                                <input type="text" name="contact_no" id="contact_no"
                                       placeholder="{{trans('file.Phone')}}" required
                                       class="form-control form-control-sm" value="{{ old('contact_no') }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{__('Date Of Birth')}} <span class="text-danger">*</span></label>
                                <input type="text" name="date_of_birth" id="date_of_birth" required autocomplete="off"
                                       class="form-control form-control-sm date" value="">
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{trans('file.Gender')}}</label>
                                <select name="gender" id="gender" class="selectpicker form-control"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{__('Selecting',['key'=>trans('file.Gender')])}}...">
                                    <option value="Male">{{trans('file.Male')}}</option>
                                    <option value="Female">{{trans('file.Female')}}</option>
                                    <option value="Other">{{trans('file.Other')}}</option>
                                </select>
                            </div>
                            <div class="col-md-8 form-group">
                                <label class="text-bold">{{trans('file.Address')}}</label>
                                <input type="text" name="address" id="address" class="form-control form-control-sm"
                                       placeholder="{{trans('file.Address')}}">
                            </div>

                            <div class="col-md-12">
                                <div class="employee-owner-panel border rounded bg-light px-2 py-2 mb-1">
                                    <div class="row align-items-end">
                                        <div class="col-md-4 form-group mb-md-0">
                                            <label class="text-bold small d-block mb-1">{{ __('Belongs To') }}</label>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="employee_owner_type" id="employee_owner_type_company" value="company" checked>
                                                <label class="form-check-label small" for="employee_owner_type_company">{{ trans('file.Company') }}</label>
                                            </div>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="employee_owner_type" id="employee_owner_type_client" value="client">
                                                <label class="form-check-label small" for="employee_owner_type_client">{{ trans('file.Client') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-8 form-group mb-0" id="employee_owner_company_wrap">
                                            <label class="text-bold small">{{ trans('file.Company') }} <span class="text-danger">*</span></label>
                                            <select name="company_id" id="company_id" required
                                                    class="form-control selectpicker dynamic employee-company-select"
                                                    data-live-search="true" data-live-search-style="contains"
                                                    data-shift_name="shift_name" data-dependent="department_name"
                                                    title="{{__('Selecting',['key'=>trans('file.Company')])}}...">
                                                @foreach($companies as $company)
                                                    <option value="{{$company->id}}">{{$company->company_name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-8 form-group mb-0 d-none" id="employee_owner_client_wrap">
                                            <label class="text-bold small">{{ trans('file.Client') }} <span class="text-danger">*</span></label>
                                            <select id="employee_client_id"
                                                    class="form-control selectpicker"
                                                    data-live-search="true" data-live-search-style="contains"
                                                    title="{{ __('Selecting', ['key' => trans('file.Client')]) }}..."
                                                    disabled>
                                                @foreach ($clients as $client)
                                                    @php
                                                        $clientName = trim(($client->first_name ?? '').' '.($client->last_name ?? ''));
                                                        $clientCompany = trim((string) ($client->company_name ?? ''));
                                                        $clientLabel = ($clientName !== '' && $clientCompany !== '')
                                                            ? $clientName.' — '.$clientCompany
                                                            : ($clientName !== '' ? $clientName : $clientCompany);
                                                    @endphp
                                                    <option value="{{ $client->id }}" data-company-id="{{ $client->resolved_company_id }}">
                                                        {{ $clientLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-12 form-group mb-0 mt-2 d-none" id="employee_owner_project_wrap">
                                            <label class="text-bold small">{{ trans('file.Projects') }}</label>
                                            <select name="project_id[]" id="employee_project_id"
                                                    class="form-control selectpicker"
                                                    data-live-search="true" data-live-search-style="contains"
                                                    multiple
                                                    title="{{ __('Selecting', ['key' => trans('file.Projects')]) }}..."
                                                    disabled>
                                            </select>
                                            <small class="text-muted">{{ __('Select client first, then assign one or more projects to this employee.') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 form-group">
                                <label class="text-bold">{{trans('file.Department')}} <span class="text-danger">*</span></label>
                                <select name="department_id" id="department_id" required
                                        class="selectpicker form-control designation"
                                        data-live-search="true" data-live-search-style="contains"
                                        data-designation_name="designation_name"
                                        title="{{__('Selecting',['key'=>trans('file.Department')])}}...">
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="text-bold">{{trans('file.Designation')}} <span class="text-danger">*</span></label>
                                <select name="designation_id" id="designation_id" required class="selectpicker form-control"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{__('Selecting',['key'=>trans('file.Designation')])}}...">
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="text-bold">{{trans('file.Office_Shift')}} <span class="text-danger">*</span></label>
                                <select name="office_shift_id" id="office_shift_id" required class="selectpicker form-control"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{__('Selecting',['key'=>trans('file.Office_Shift')])}}...">
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="text-bold">{{ __('Work Location') }}</label>
                                <select name="location_id" id="location_id" class="selectpicker form-control"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{ __('Selecting', ['key' => __('Work Location')]) }}...">
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}"
                                                data-company-ids="{{ $location->companies->pluck('id')->implode(',') }}">
                                            {{ $location->location_name }}
                                            @if($location->max_radius) ({{ $location->max_radius }}m) @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 form-group">
                                <label class="text-bold">{{trans('file.Username')}} <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username"
                                       placeholder="{{__('Username')}}"
                                       required class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="text-bold">{{trans('file.Role')}} <span class="text-danger">*</span></label>
                                <select name="role_users_id" id="role_users_id" required
                                        class="selectpicker form-control"
                                        data-live-search="true" data-live-search-style="contains"
                                        title="{{__('Selecting',['key'=>trans('file.Role')])}}...">
                                    @foreach ($roles as $item)
                                        <option value="{{$item->id}}">{{$item->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="text-bold">{{trans('file.Password')}} <span class="text-danger">*</span></label>
                                <input type="password" name="password" id="password"
                                       placeholder="{{trans('file.Password')}}"
                                       required class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="text-bold">{{__('Confirm Password')}} <span class="text-danger">*</span></label>
                                <input id="confirm_pass" type="password" class="form-control form-control-sm"
                                       name="password_confirmation" placeholder="{{__('Confirm')}}"
                                       required autocomplete="new-password">
                                <small id="divCheckPasswordMatch" class="registrationFormAlert text-muted"></small>
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{__('Attendance Type')}} <span class="text-danger">*</span></label>
                                <select name="attendance_type" id="attendance_type" required class="selectpicker form-control"
                                        data-live-search="true" data-live-search-style="contains" title="{{__('Select Attendance Type...')}}">
                                    <option value="general">{{__('General')}}</option>
                                    <option value="location_based">{{__('Location Based')}}</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{__('Date Of Joining')}} <span class="text-danger">*</span></label>
                                <input type="text" name="joining_date" id="joining_date" class="form-control form-control-sm date">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="text-bold">{{ __('Image') }}</label>
                                <input type="file" id="profile_photo"
                                       class="form-control form-control-sm @error('photo') is-invalid @enderror"
                                       name="profile_photo">
                            </div>

                            <div class="col-12 form-group mb-0 mt-1">
                                <input type="hidden" name="action" id="action"/>
                                <input type="hidden" name="hidden_id" id="hidden_id"/>
                                <input type="submit" name="action_button" id="action_button" class="btn btn-warning btn-sm w-100" value="{{trans('file.Add')}}" />
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>


    <div id="confirmModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">{{trans('file.Confirmation')}}</h2>
                    <button type="button" class="employee-close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <h4 align="center" style="margin:0;">{{__('Are you sure you want to remove this data?')}}</h4>
                </div>
                <div class="modal-footer">
                    <button type="button" name="ok_button" id="ok_button"
                            class="btn btn-danger">{{trans('file.OK')}}</button>
                    <button type="button" class="close btn-default"
                            data-dismiss="modal">{{trans('file.Cancel')}}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
<style>
    #formModal .employee-add-modal-body {
        padding: 0.85rem 1rem 0.65rem;
        max-height: calc(100vh - 8rem);
        overflow-y: auto;
    }

    #formModal .employee-add-form [class*="col-"] {
        min-width: 0;
    }

    #formModal .employee-add-form .form-group {
        margin-bottom: 0.55rem;
    }

    #formModal .employee-add-form label.text-bold {
        font-size: 0.8rem;
        margin-bottom: 0.2rem;
    }

    #formModal .employee-add-form input.form-control-sm,
    #formModal .employee-add-form input.form-control.date {
        height: calc(1.5em + 0.5rem + 2px);
        font-size: 0.875rem;
    }

    #formModal .employee-add-form .bootstrap-select {
        display: block !important;
        width: 100% !important;
        max-width: 100%;
    }

    #formModal .employee-add-form .bootstrap-select > .dropdown-toggle {
        width: 100% !important;
        min-height: 34px;
        padding: 0.35rem 1.85rem 0.35rem 0.55rem;
        font-size: 0.875rem;
        position: relative;
        display: flex;
        align-items: center;
        overflow: hidden;
    }

    #formModal .employee-add-form .bootstrap-select .filter-option-inner-inner {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #formModal .employee-add-form .bootstrap-select > .dropdown-toggle::after {
        position: absolute;
        right: 0.65rem;
        top: 50%;
        margin-top: 0;
        transform: translateY(-50%);
    }

    #formModal .employee-owner-panel {
        background: #f8f9fc;
    }

    #formModal .bootstrap-select .dropdown-menu {
        z-index: 1060;
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript">
    var hrmsClientCompanyMap = @json($clients->mapWithKeys(fn ($client) => [(string) $client->id => $client->resolved_company_id])->filter());

    function hrmsCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || $('#sample_form input[name="_token"]').val() || '';
    }

    function repopulateEmployeeSelect($select, html) {
        if ($select.data('selectpicker')) {
            $select.selectpicker('destroy');
        }
        $select.html(html);
        $select.selectpicker({
            width: '100%',
            liveSearch: true,
            liveSearchStyle: 'contains'
        });
    }

    function refreshFormSelectpicker($select) {
        if ($select.data('selectpicker')) {
            $select.selectpicker('refresh');
        } else {
            $select.selectpicker({ width: '100%', liveSearch: true, liveSearchStyle: 'contains' });
        }
    }

    function formatCnicValue(value) {
        const digits = (value || '').replace(/\D/g, '').slice(0, 13);
        if (digits.length <= 5) return digits;
        if (digits.length <= 12) return digits.slice(0, 5) + '-' + digits.slice(5);
        return digits.slice(0, 5) + '-' + digits.slice(5, 12) + '-' + digits.slice(12);
    }

    $(document).on('input', '.cnic-input', function () {
        this.value = formatCnicValue(this.value);
    });

    $(document).ready(function () {

        if (window.location.href.indexOf('#formModal') != -1) {
            $('#formModal').modal('show');
        }

        const urlParams = new URLSearchParams(window.location.search);
        const lockedLocationId = @json($filterLocationId ?? null) || urlParams.get('location_id');
        const preselectedClientId = urlParams.get('client_id');
        if (preselectedClientId) {
            $('#client_id_filter').selectpicker('val', preselectedClientId);
            $('#collapseExample').collapse('show');
        }

        var date = $('.date');
        date.datepicker({
            format: '{{ env('Date_Format_JS')}}',
            autoclose: true,
            todayHighlight: true
        });

        const teamLeaderViewOnly = @json(!empty($teamLeaderViewOnly));

        var table_table = $('#employee-table').DataTable({
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
                url: "{{ route('employees.index') }}",
                type: 'GET',
                data: function (d) {
                    d.client_id      = $('#client_id_filter').val();
                    d.company_id     = $("#company_id_filter").val();
                    d.department_id  = $('#department_id_filter').val();
                    d.designation_id = $('#designation_id_filter').val();
                    d.office_shift_id = $('#office_shift_id_filter').val();
                    if (lockedLocationId) {
                        d.location_id = lockedLocationId;
                    }
                }
            },

            columns: [

                {
                    data: 'id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name',

                },
                {
                    data: 'company',
                    name: 'company',
                },
                {
                    data: 'contacts',
                    name: 'contacts',
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false
                }
            ],


            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{__('records per page')}}',
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
                    'targets': [0,4],
                    "className": "text-left"
                },
                {
                    'render': function (data, type, row, meta) {
                        if (teamLeaderViewOnly) {
                            return '';
                        }
                        if (type == 'display') {
                            data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label class="text-bold"></label></div>';
                        }

                        return data;
                    },
                    'checkboxes': {
                        'selectRow': true,
                        'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label class="text-bold"></label></div>'
                    },
                    'targets': [0]
                }
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
                // {
                //     extend: 'csv',
                //     text: '<i title="export for device" class="fa fa-tablet"></i>',
                //     className: 'export-for-device',
                //     exportOptions: {
                //         columns: [1,2],
                //         rows: ':visible',
                //         format: {
                //             body: function ( data, row, column, node ) {
                //                 if (column === 0) {
                //                     var id = data.match(/<span>Staff Id: (.*?)<\/span>/)[1];
                //                     name = data.match(/<[a][^>]*>(.+?)<\/[a]>/)[1];
                //                     return id;
                //                 }
                //                 else {
                //                     return name;
                //                 }
                //             }
                //         }
                //     },
                //     customize: function (csv) {
                //         var csvRows = csv.split('\n');
                //         csvRows[0] = csvRows[0].replace(['"Employee"', '"Company"'], ['"Staff Id"','"Name"']);
                //         return csvRows.join('\n');
                //     }
                // },
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

    });


    //-------------- Filter -----------------------

    $('#filterSubmit').on("click",function(e){
        $('#employee-table').DataTable().draw(true);
    });

    $('#client_id_filter').on('changed.bs.select', function () {
        if ($(this).val()) {
            $('#company_id_filter').selectpicker('val', '');
            $('#department_id_filter').html('');
            $('#designation_id_filter').html('');
            $('#office_shift_id_filter').html('');
            refreshFormSelectpicker($('#department_id_filter'));
            refreshFormSelectpicker($('#designation_id_filter'));
            refreshFormSelectpicker($('#office_shift_id_filter'));
        }
    });

    $('#company_id_filter').on('changed.bs.select', function () {
        if ($(this).val()) {
            $('#client_id_filter').selectpicker('val', '');
        }
    });
    //--------------/ Filter ----------------------


    $('#create_record').click(function () {
        $('.modal-title').text("Add Employee");
        $('#action_button').val('{{trans('file.Add')}}');
        $('#action').val('{{trans('file.Add')}}');
        toggleEmployeeOwnerType('company');
        $('#formModal').modal('show');
    });

    $('#formModal').on('shown.bs.modal', function () {
        $('#formModal .selectpicker').each(function () {
            if (!$(this).data('selectpicker')) {
                $(this).selectpicker({ width: '100%', liveSearch: true, liveSearchStyle: 'contains' });
            } else {
                $(this).selectpicker('render');
            }
        });
    });

    function toggleEmployeeOwnerType(type) {
        type = type || 'company';
        $('input[name="employee_owner_type"][value="' + type + '"]').prop('checked', true);

        if (type === 'client') {
            $('#employee_owner_company_wrap').addClass('d-none');
            $('#employee_owner_client_wrap').removeClass('d-none');
            $('#employee_owner_project_wrap').removeClass('d-none');
            $('#company_id').prop('disabled', true).removeAttr('name').removeAttr('required');
            $('#employee_client_id').prop('disabled', false).attr('name', 'client_id').attr('required', 'required');
            $('#employee_client_id').selectpicker('val', '');
            clearEmployeeProjectSelectAdd();
        } else {
            $('#employee_owner_client_wrap').addClass('d-none');
            $('#employee_owner_project_wrap').addClass('d-none');
            $('#employee_owner_company_wrap').removeClass('d-none');
            $('#employee_client_id').prop('disabled', true).removeAttr('name').removeAttr('required');
            $('#company_id').prop('disabled', false).attr('name', 'company_id').attr('required', 'required');
            $('#company_id').selectpicker('val', '');
            clearEmployeeProjectSelectAdd();
        }

        repopulateEmployeeSelect($('#department_id'), '');
        repopulateEmployeeSelect($('#designation_id'), '');
        repopulateEmployeeSelect($('#office_shift_id'), '');

        if ($('#company_id').data('selectpicker')) {
            $('#company_id').selectpicker('destroy');
        }
        $('#company_id').selectpicker({ width: '100%', liveSearch: true, liveSearchStyle: 'contains' });

        if ($('#employee_client_id').data('selectpicker')) {
            $('#employee_client_id').selectpicker('destroy');
        }
        $('#employee_client_id').selectpicker({ width: '100%', liveSearch: true, liveSearchStyle: 'contains' });

        filterLocationOptionsByCompany();
    }

    function clearEmployeeProjectSelectAdd() {
        var $project = $('#employee_project_id');
        if (!$project.length) {
            return;
        }
        if ($project.data('selectpicker')) {
            $project.selectpicker('destroy');
        }
        $project.html('').prop('disabled', true);
        $project.selectpicker({ width: '100%', liveSearch: true, liveSearchStyle: 'contains' });
    }

    function loadEmployeeClientProjectsAdd() {
        var $project = $('#employee_project_id');
        var clientId = $('#employee_client_id').val();

        if (!$project.length) {
            return;
        }

        if (!clientId || $('input[name="employee_owner_type"]:checked').val() !== 'client') {
            clearEmployeeProjectSelectAdd();
            return;
        }

        $.ajax({
            url: "{{ route('dynamic_client_projects') }}",
            method: 'POST',
            data: {
                _token: hrmsCsrfToken(),
                client_id: clientId
            },
            success: function (result) {
                if ($project.data('selectpicker')) {
                    $project.selectpicker('destroy');
                }
                $project.html(result).prop('disabled', false).attr('name', 'project_id[]');
                $project.selectpicker({ width: '100%', liveSearch: true, liveSearchStyle: 'contains' });
            },
            error: function (xhr) {
                console.error('Client projects load failed', xhr.status, xhr.responseText);
                clearEmployeeProjectSelectAdd();
            }
        });
    }

    $('input[name="employee_owner_type"]').on('change', function () {
        toggleEmployeeOwnerType($(this).val());
    });

    function getEmployeeFormCompanyId() {
        if ($('input[name="employee_owner_type"]:checked').val() === 'client') {
            var clientId = String($('#employee_client_id').val() || '');
            return String(hrmsClientCompanyMap[clientId] || $('#employee_client_id option:selected').attr('data-company-id') || '');
        }

        return String($('#company_id').val() || '');
    }

    function loadEmployeeDepartments() {
        var ownerType = $('input[name="employee_owner_type"]:checked').val();
        var payload = {
            _token: hrmsCsrfToken(),
            dependent: 'department_name'
        };

        if (ownerType === 'client') {
            payload.client_id = $('#employee_client_id').val();
            payload.value = hrmsClientCompanyMap[payload.client_id]
                || $('#employee_client_id option:selected').attr('data-company-id')
                || '';

            if (!payload.client_id) {
                return;
            }
        } else {
            payload.value = $('#company_id').val();

            if (!payload.value) {
                return;
            }
        }

        $.ajax({
            url: "{{ route('dynamic_department') }}",
            method: 'POST',
            data: payload,
            success: function (result) {
                repopulateEmployeeSelect($('#department_id'), result);
                repopulateEmployeeSelect($('#designation_id'), '');
                loadEmployeeOfficeShifts();
            },
            error: function (xhr) {
                console.error('Department load failed', xhr.status, xhr.responseText);
                repopulateEmployeeSelect($('#department_id'), '');
                repopulateEmployeeSelect($('#designation_id'), '');
            }
        });
    }

    function loadEmployeeOfficeShifts() {
        var ownerType = $('input[name="employee_owner_type"]:checked').val();
        var payload = {
            _token: hrmsCsrfToken(),
            dependent: 'shift_name'
        };

        if (ownerType === 'client') {
            payload.client_id = $('#employee_client_id').val();

            if (!payload.client_id) {
                repopulateEmployeeSelect($('#office_shift_id'), '');
                return;
            }
        } else {
            payload.value = $('#company_id').val();

            if (!payload.value) {
                repopulateEmployeeSelect($('#office_shift_id'), '');
                return;
            }
        }

        $.ajax({
            url: "{{ route('dynamic_office_shifts') }}",
            method: 'POST',
            data: payload,
            success: function (result) {
                repopulateEmployeeSelect($('#office_shift_id'), result);
            },
            error: function (xhr) {
                console.error('Office shift load failed', xhr.status, xhr.responseText);
                repopulateEmployeeSelect($('#office_shift_id'), '');
            }
        });
    }

    $(document).on('change changed.bs.select', '#sample_form #company_id', function () {
        if ($('input[name="employee_owner_type"]:checked').val() !== 'company') {
            return;
        }

        filterLocationOptionsByCompany();

        if ($(this).val()) {
            loadEmployeeDepartments();
        }
    });

    $(document).on('change changed.bs.select', '#sample_form #employee_client_id', function () {
        if ($('input[name="employee_owner_type"]:checked').val() !== 'client') {
            return;
        }

        if ($(this).val()) {
            loadEmployeeDepartments();
            loadEmployeeClientProjectsAdd();
            filterLocationOptionsByCompany();
        } else {
            clearEmployeeProjectSelectAdd();
        }
    });

    $(document).on('change changed.bs.select', '#sample_form #department_id', function () {
        if (!$(this).val()) {
            return;
        }

        $.ajax({
            url: "{{ route('dynamic_designation_department') }}",
            method: 'POST',
            data: {
                value: $(this).val(),
                _token: hrmsCsrfToken(),
                designation_name: $(this).data('designation_name')
            },
            success: function (result) {
                repopulateEmployeeSelect($('#designation_id'), result);
            },
            error: function (xhr) {
                console.error('Designation load failed', xhr.status, xhr.responseText);
                repopulateEmployeeSelect($('#designation_id'), '');
            }
        });
    });

    $('#sample_form').on('submit', function (event) {
        event.preventDefault();

        var $form = $(this);
        $form.find('.selectpicker').each(function () {
            var $select = $(this);
            if ($select.data('selectpicker')) {
                $select.selectpicker('refresh');
            }
        });

        $.ajax({
            url: "{{ route('employees.store') }}",
            method: "POST",
            data: new FormData(this),
            contentType: false,
            cache: false,
            processData: false,
            dataType: "json",
            success: function (data) {
                var result = typeof window.hrmsSwalResponse === 'function'
                    ? window.hrmsSwalResponse(data)
                    : null;

                if (result === 'success') {
                    $('#employee-table').DataTable().ajax.reload();
                    setTimeout(function () {
                        $('#formModal').modal('hide');
                        $('#sample_form')[0].reset();
                        $('select').selectpicker('refresh');
                        $('.date').datepicker('update');
                        $('#form_result').html('').hide();
                        toggleEmployeeOwnerType('company');
                    }, 2200);
                }
            },
            error: function (xhr) {
                var payload = xhr.responseJSON || null;

                if (!payload && xhr.responseText) {
                    try {
                        payload = JSON.parse(xhr.responseText);
                    } catch (e) {
                        payload = null;
                    }
                }

                if (payload && (payload.errors || payload.error || payload.message)) {
                    if (typeof window.hrmsSwalResponse === 'function') {
                        window.hrmsSwalResponse(payload);
                    }
                    return;
                }

                if (typeof window.hrmsSwalResponse === 'function') {
                    window.hrmsSwalResponse(null, {
                        fallbackError: '{{ __('Something went wrong. Please try again.') }}'
                    });
                }
            }
        });
    });


    let employee_delete_id;

    $(document).on('click', '.delete', function () {
        employee_delete_id = $(this).attr('id');
        $('#confirmModal').modal('show');
        $('.modal-title').text('{{__('DELETE Record')}}');
        $('#ok_button').text('{{trans('file.OK')}}');

    });


    $(document).on('click', '#bulk_delete', function () {
        var id = [];
        let table = $('#employee-table').DataTable();
        id = table.rows({selected: true}).ids().toArray();
        if (id.length > 0) {
            if (confirm('{{__('Delete Selection',['key'=>trans('file.Employee')])}}')) {
                $.ajax({
                    url: '{{route('mass_delete_employees')}}',
                    method: 'POST',
                    data: {
                        employeeIdArray: id
                    },
                    success: function (data) {
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                        }
                        if (data.error) {
                            html = '<div class="alert alert-danger">' + data.error + '</div>';
                        }
                        table.ajax.reload();
                        table.rows('.selected').deselect();
                        $('#general_result').html(html).slideDown(300).delay(5000).slideUp(300);

                    }

                });
            }
        } else {
            alert('{{__('Please select atleast one checkbox')}}');
        }
    });


    $('#close').click(function () {
        $('#sample_form')[0].reset();
        $('select').selectpicker('refresh');
        $('.date').datepicker('update');
        $('#employee-table').DataTable().ajax.reload();
    });

    $('#ok_button').click(function () {
        let target = "{{ route('employees.index') }}/" + employee_delete_id + '/delete';
        $.ajax({
            url: target,
            beforeSend: function () {
                $('#ok_button').text('{{trans('file.Deleting...')}}');
            },
            success: function (data) {
                if (data.success) {
                    html = '<div class="alert alert-success">' + data.success + '</div>';
                }
                if (data.error) {
                    html = '<div class="alert alert-danger">' + data.error + '</div>';
                }
                setTimeout(function () {
                    $('#general_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    $('#confirmModal').modal('hide');
                    $('#employee-table').DataTable().ajax.reload();
                }, 2000);
            }
        })
    });


    $('#confirm_pass').on('input', function () {

        if ($('input[name="password"]').val() != $('input[name="password_confirmation"]').val())
            $("#divCheckPasswordMatch").html('{{__('Password does not match! please type again')}}');
        else
            $("#divCheckPasswordMatch").html('{{__('Password matches!')}}');

    });


    function filterLocationOptionsByCompany() {
        const selectedCompany = getEmployeeFormCompanyId();
        $('#location_id option').each(function () {
            const raw = String($(this).data('company-ids') || '');
            const companyIds = raw ? raw.split(',') : [];
            const visible = !selectedCompany || companyIds.length === 0 || companyIds.includes(selectedCompany);
            $(this).toggle(visible);
        });
        $('#location_id').selectpicker('refresh');
    }

    toggleEmployeeOwnerType('company');


    $('#company_id_filter').on('changed.bs.select', function () {
        if ($(this).val() === '') {
            return;
        }

        let value = $(this).val();
        let _token = $('input[name="_token"]').val();

        $.post("{{ route('dynamic_department') }}", {
            value: value,
            _token: _token,
            dependent: 'department_name'
        }).done(function (result) {
            repopulateEmployeeSelect($('#department_id_filter'), result);
        });

        $.post("{{ route('dynamic_office_shifts') }}", {
            value: value,
            _token: _token,
            dependent: 'shift_name'
        }).done(function (result) {
            repopulateEmployeeSelect($('#office_shift_id_filter'), result);
        });
    });

    $(document).on('change', '#department_id_filter', function () {
        if (!$(this).val()) {
            return;
        }

        $.ajax({
            url: "{{ route('dynamic_designation_department') }}",
            method: 'POST',
            data: {
                value: $(this).val(),
                _token: hrmsCsrfToken(),
                designation_name: $(this).data('designation_name')
            },
            success: function (result) {
                repopulateEmployeeSelect($('#designation_id_filter'), result);
            }
        });
    });

    $('.designationFilter').change(function () {
        if ($(this).val() !== '') {
            let value = $('#department_id_filter').val();
            let designation_name = $(this).data('designation_name');
            let _token = $('input[name="_token"]').val();
            $.ajax({
                url: "{{ route('dynamic_designation_department') }}",
                method: "POST",
                data: {value: value, _token: _token, designation_name: designation_name},
                success: function (result) {
                    $('#designation_id_filter').html(result);
                    refreshFormSelectpicker($('#designation_id_filter'));
                }
            });
        }
    });


    // Login Type Change
    // $('#login_type').change(function() {
    //     var login_type = $('#login_type').val();
    //     if (login_type=='ip') {
    //         data = '<label class="text-bold">{{__("IP Address")}} <span class="text-danger">*</span></label>';
    //         data += '<input type="text" name="ip_address" id="ip_address" placeholder="Type IP Address" required class="form-control">';
    //         $('#ipField').html(data)
    //     }else{
    //         $('#ipField').empty();
    //     }
    // });



    //--------  Filter  ---------
    // Handled above in scoped .dynamic / .designation handlers (company_id_filter, etc.)

</script>
@endpush
