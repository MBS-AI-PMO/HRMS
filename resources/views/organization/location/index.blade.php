@extends('layout.main')
@section('content')



    <section>

        <div class="container-fluid"><span id="general_result"></span></div>


        <div class="container-fluid mb-3">
            @can('store-location')
                <button type="button" class="btn btn-info" name="create_record" id="create_record"><i
                            class="fa fa-plus"></i> {{__('Add Location')}}</button>
            @endcan
            @can('delete-location')
                <button type="button" class="btn btn-danger" name="bulk_delete" id="bulk_delete"><i
                            class="fa fa-minus-circle"></i> {{__('Bulk delete')}}</button>
            @endcan
        </div>

        <div class="card mb-4">
            <div class="card-header with-border">
                <h3 class="card-title mb-0">{{ __('Filter Locations') }}</h3>
            </div>
            <div class="card-body">
                <div class="row location-hierarchy-flow" id="location_filter_flow">
                    <div class="col-md-2 form-group">
                        <label>{{ trans('file.Company') }}</label>
                        <select id="filter_company_id" class="form-control selectpicker hierarchy-select"
                                data-live-search="true" data-live-search-style="contains"
                                title="{{ __('All') }}">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ trans('file.Client') }}</label>
                        <select id="filter_client_id" class="form-control selectpicker hierarchy-select" disabled
                                data-live-search="true" data-live-search-style="contains"
                                title="{{ __('All') }}">
                            <option value="">{{ __('All') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ trans('file.Project') }}</label>
                        <select id="filter_project_id" class="form-control selectpicker hierarchy-select" disabled
                                data-live-search="true" data-live-search-style="contains"
                                title="{{ __('All') }}">
                            <option value="">{{ __('All') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ trans('file.Department') }}</label>
                        <select id="filter_department_id" class="form-control selectpicker hierarchy-select" disabled
                                data-live-search="true" data-live-search-style="contains"
                                title="{{ __('All') }}">
                            <option value="">{{ __('All') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ trans('file.Employee') }}</label>
                        <select id="filter_employee_id" class="form-control selectpicker hierarchy-select" disabled
                                data-live-search="true" data-live-search-style="contains"
                                title="{{ __('All') }}">
                            <option value="">{{ __('All') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-group d-flex align-items-end">
                        <button type="button" class="btn btn-primary btn-block" id="apply_location_filters">
                            <i class="fa fa-filter"></i> {{ trans('file.Search') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <div class="table-responsive">
            <table id="location-table" class="table ">
                <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Location')}}</th>
                    <th>{{ __('Company / Client') }}</th>
                    <th>{{__('Location Head')}}</th>
                    <th>{{__('Address Line 1')}}</th>
                    <th>{{__('Address Line 2')}}</th>
                    <th>{{trans('file.City')}}</th>
                    <th>{{trans('file.State')}}</th>
                    <th>{{trans('file.Country')}}</th>
                    <th>{{trans('file.ZIP')}}</th>
                    <th>{{ __('Latitude') }}</th>
                    <th>{{ __('Longitude') }}</th>
                    <th>{{ __('Max Radius (meters)') }}</th>
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
                </thead>

            </table>
        </div>
    </section>



    <div id="formModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('Add Location')}}</h5>
                    <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><i class="dripicons-cross"></i></button>
                </div>

                <div class="modal-body">
                    <span id="form_result"></span>
                    <form method="post" id="sample_form" class="form-horizontal">

                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Location')}} *</label>
                                <input type="text" name="location_name" id="location_name" required class="form-control"
                                       placeholder="{{__('Unique Value',['key'=>trans('file.Location')])}}">
                            </div>

                            <div class="col-md-4 form-group">
                                <label>{{ trans('file.Company') }} *</label>
                                <select name="owner_company_id" id="owner_company_id" required
                                        class="form-control selectpicker"
                                        data-live-search="true"
                                        data-live-search-style="contains"
                                        title='{{ __('Selecting', ['key' => trans('file.Company')]) }}...'>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="owner_type" id="owner_type_hidden" value="company">
                            </div>

                            <div class="col-md-4 form-group">
                                <label>{{__('Location Head')}}</label>
                                <select name="location_head_ids[]" id="location_head" class="form-control selectpicker"
                                        data-live-search="true" data-live-search-style="contains" multiple
                                        title='{{__('Selecting',['key'=>trans('file.Employee')])}}...'
                                        disabled>
                                </select>
                                <small class="text-muted">{{ __('Select company first to load employees.') }}</small>
                            </div>


                            <div class="col-md-6 form-group">
                                <label>{{__('Address Line 1')}} *</label>
                                <input type="text" name="address1" id="address1" required class="form-control"
                                       placeholder="full address">
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{__('Address Line 2')}} </label>
                                <input type="text" name="address2" id="address2" class="form-control"
                                       placeholder={{trans("file.Optional")}}>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{trans('file.City')}} </label>
                                <input type="text" name="city" id="city" class="form-control"
                                       placeholder={{trans("file.Optional")}}>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{trans('file.State')}} </label>
                                <input type="text" name="state" id="state" class="form-control"
                                       placeholder={{trans("file.Optional")}}>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{trans('file.Country')}}</label>
                                <select name="country" id="country" class="form-control selectpicker"
                                        data-live-search="true" data-live-search-style="contains"
                                        title='{{__('Selecting',['key'=>trans('file.Country')])}}...'>
                                    @foreach($countries as $country)
                                        <option value="{{$country->id}}">{{$country->name}}</option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="col-md-6 form-group">
                                <label>{{trans('file.ZIP')}} </label>
                                <input type="text" name="zip" id="zip" class="form-control"
                                       placeholder={{trans("file.Optional")}}>
                            </div>

                            <div class="col-md-4 form-group">
                                <label>{{ __('Latitude') }}</label>
                                <input type="number" step="0.0000001" name="latitude" id="latitude" class="form-control"
                                       placeholder="{{ trans('file.Optional') }}">
                            </div>

                            <div class="col-md-4 form-group">
                                <label>{{ __('Longitude') }}</label>
                                <input type="number" step="0.0000001" name="longitude" id="longitude" class="form-control"
                                       placeholder="{{ trans('file.Optional') }}">
                            </div>

                            <div class="col-md-4 form-group">
                                <label>{{ __('Max Radius (meters)') }}</label>
                                <input type="number" step="0.01" min="0" name="max_radius" id="max_radius" class="form-control"
                                       placeholder="{{ trans('file.Optional') }}">
                            </div>

                            <div class="form-group" align="center">
                                <input type="hidden" name="action" id="action"/>
                                <input type="hidden" name="hidden_id" id="hidden_id"/>
                                <input type="submit" name="action_button" id="action_button" class="btn btn-warning"
                                       value={{trans('file.Add')}} />
                            </div>

                        </div>


                    </form>

                </div>
            </div>
        </div>
    </div>






    <div id="shiftAssignModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Assign Shift to Location Employees') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <span id="shift_assign_result"></span>
                    <p class="mb-2">
                        <strong>{{ trans('file.Location') }}:</strong>
                        <span id="shift_location_name">-</span>
                    </p>
                    <p class="text-muted mb-3" id="shift_employee_summary"></p>
                    <form id="shift_assign_form">
                        @csrf
                        <input type="hidden" name="location_id" id="shift_location_id">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                <tr>
                                    <th>{{ trans('file.Client') }}</th>
                                    <th>{{ __('Employees at Location') }}</th>
                                    <th>{{ trans('file.Office_Shift') }}</th>
                                </tr>
                                </thead>
                                <tbody id="shift_assign_rows"></tbody>
                            </table>
                        </div>
                        <small class="text-muted d-block mt-2">
                            {{ __('Select a shift for each company. All active employees assigned to this location will be updated together.') }}
                        </small>
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary" id="shift_assign_submit">
                                {{ __('Update Shifts') }}
                            </button>
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
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h2 class="modal-title">{{trans('file.Confirmation')}}</h2>
                </div>
                <div class="modal-body">
                    <h4 align="center">{{__('Are you sure you want to remove this data?')}}</h4>
                </div>
                <div class="modal-footer">
                    <button type="button" name="ok_button" id="ok_button" class="btn btn-danger">{{trans('file.OK')}}'
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{trans('file.Cancel')}}</button>
                </div>
            </div>
        </div>
    </div>



@endsection

@push('css')
<style>
    #formModal .location-hierarchy-panel,
    .location-hierarchy-flow .form-group label {
        font-weight: 600;
    }

    #formModal .location-hierarchy-panel {
        background: #f8f9fc;
    }

    #formModal .bootstrap-select .dropdown-menu {
        z-index: 1060;
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript">
    (function($) {
        "use strict";
        $(document).ready(function () {
            $('#location_filter_flow .selectpicker').selectpicker();

            $('#location-table').DataTable({
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
                        });
                    });
                },
                responsive: true,
                fixedHeader: {
                    header: true,
                    footer: true
                },
                serverSide: true,
                ajax: {
                    url: "{{ route('locations.index') }}",
                    data: function (data) {
                        data.filter_company_id = $('#filter_company_id').val();
                        data.filter_client_id = $('#filter_client_id').val();
                        data.filter_project_id = $('#filter_project_id').val();
                        data.filter_department_id = $('#filter_department_id').val();
                        data.filter_employee_id = $('#filter_employee_id').val();
                    }
                },
                createdRow: function (row, data, dataIndex) {
                    $(row).find('td:eq(0) .dt-checkboxes').attr('data-id', data.id);
                },
                columns: [

                    {
                        data: 'id',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'location_name',
                        name: 'location_name',

                    },
                    {
                        data: 'companies',
                        name: 'companies'
                    },
                    {
                        data: 'location_head',
                        name: 'location_head'
                    },
                    {
                        data: 'address1',
                        name: 'address1'
                    },
                    {
                        data: 'address2',
                        name: 'address2'
                    },
                    {
                        data: 'city',
                        name: 'city'
                    },
                    {
                        data: 'state',
                        name: 'state'
                    },
                    {
                        data: 'country',
                        name: 'country'
                    },
                    {
                        data: 'zip',
                        name: 'zip'
                    },
                    {
                        data: 'latitude',
                        name: 'latitude'
                    },
                    {
                        data: 'longitude',
                        name: 'longitude'
                    },
                    {
                        data: 'max_radius',
                        name: 'max_radius'
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
                        'targets': [0, 13]
                    },
                    {
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
        });


        var editLocationData = null;

        var hierarchyRoutes = {
            clients: @json(route('locations.hierarchy.clients')),
            projects: @json(route('locations.hierarchy.projects')),
            departments: @json(route('locations.hierarchy.departments')),
            employees: @json(route('locations.hierarchy.employees')),
        };

        function refreshHierarchySelect($select) {
            if ($select.data('selectpicker')) {
                $select.selectpicker('refresh');
            } else {
                $select.selectpicker();
            }
        }

        function resetHierarchySelect($select, disabled, placeholder) {
            $select.prop('disabled', !!disabled);
            $select.html('<option value="">' + (placeholder || '{{ __('All') }}') + '</option>');
            refreshHierarchySelect($select);
        }

        function populateHierarchySelect($select, items, selectedValue, disabled) {
            var placeholder = $select.find('option:first').text() || '{{ __('All') }}';
            $select.prop('disabled', !!disabled);
            $select.html('<option value="">' + placeholder + '</option>');
            (items || []).forEach(function (item) {
                $select.append($('<option>', { value: item.id, text: item.label }));
            });
            if (selectedValue) {
                $select.val(String(selectedValue));
            }
            refreshHierarchySelect($select);
        }

        function populateLocationHeadSelect(items, selectedIds) {
            var $select = $('#location_head');
            var selected = (selectedIds || []).map(String);
            $select.prop('disabled', false);
            $select.empty();
            (items || []).forEach(function (item) {
                $select.append($('<option>', { value: item.id, text: item.label }));
            });
            selected.forEach(function (headId) {
                if (!$select.find('option[value="' + headId + '"]').length) {
                    $select.append($('<option>', {
                        value: headId,
                        text: '{{ __('Employee') }} #' + headId
                    }));
                }
            });
            refreshHierarchySelect($select);
            $select.selectpicker('val', selected);
        }

        function fetchHierarchyItems(url, params) {
            return $.getJSON(url, params).then(function (response) {
                return response.items || [];
            });
        }

        function getHierarchyParams(prefix) {
            return {
                company_id: $('#' + prefix + 'company_id').val(),
                client_id: $('#' + prefix + 'client_id').val(),
                project_id: $('#' + prefix + 'project_id').val(),
                department_id: $('#' + prefix + 'department_id').val(),
            };
        }

        function loadLocationHeadsByCompany(companyId, selectedIds) {
            if (!companyId) {
                $('#location_head').prop('disabled', true).empty();
                refreshHierarchySelect($('#location_head'));
                $('#location_head').selectpicker('val', []);
                return $.Deferred().resolve().promise();
            }

            return fetchHierarchyItems(hierarchyRoutes.employees, { company_id: companyId })
                .then(function (items) {
                    populateLocationHeadSelect(items, selectedIds || []);
                });
        }

        function loadHierarchyClients(prefix, selectedClientId) {
            var companyId = $('#' + prefix + 'company_id').val();
            resetHierarchySelect($('#' + prefix + 'project_id'), true);
            resetHierarchySelect($('#' + prefix + 'department_id'), true);
            resetHierarchySelect($('#' + prefix + 'employee_id'), true);

            if (!companyId) {
                resetHierarchySelect($('#' + prefix + 'client_id'), true);
                return $.Deferred().resolve().promise();
            }

            return fetchHierarchyItems(hierarchyRoutes.clients, { company_id: companyId })
                .then(function (items) {
                    populateHierarchySelect($('#' + prefix + 'client_id'), items, selectedClientId, false);
                });
        }

        function loadHierarchyProjects(prefix, selectedProjectId) {
            var params = getHierarchyParams(prefix);
            resetHierarchySelect($('#' + prefix + 'department_id'), true);
            resetHierarchySelect($('#' + prefix + 'employee_id'), true);

            if (!params.company_id || !params.client_id) {
                resetHierarchySelect($('#' + prefix + 'project_id'), true);
                return $.Deferred().resolve().promise();
            }

            return fetchHierarchyItems(hierarchyRoutes.projects, params)
                .then(function (items) {
                    populateHierarchySelect($('#' + prefix + 'project_id'), items, selectedProjectId, false);
                });
        }

        function loadHierarchyDepartments(prefix, selectedDepartmentId) {
            var params = getHierarchyParams(prefix);
            resetHierarchySelect($('#' + prefix + 'employee_id'), true);

            if (!params.company_id || !params.client_id) {
                resetHierarchySelect($('#' + prefix + 'department_id'), true);
                return $.Deferred().resolve().promise();
            }

            return fetchHierarchyItems(hierarchyRoutes.departments, params)
                .then(function (items) {
                    populateHierarchySelect($('#' + prefix + 'department_id'), items, selectedDepartmentId, false);
                });
        }

        function loadHierarchyEmployees(prefix, selectedEmployeeId) {
            var params = getHierarchyParams(prefix);

            if (!params.company_id || !params.client_id) {
                resetHierarchySelect($('#' + prefix + 'employee_id'), true);
                return $.Deferred().resolve().promise();
            }

            return fetchHierarchyItems(hierarchyRoutes.employees, params)
                .then(function (items) {
                    populateHierarchySelect($('#' + prefix + 'employee_id'), items, selectedEmployeeId, false);
                });
        }

        function applyEditLocationOwner(editData) {
            $('#owner_type_hidden').val('company');
            var companyId = editData.owner_company_id ? String(editData.owner_company_id) : '';
            $('#owner_company_id').selectpicker('val', companyId);

            return loadLocationHeadsByCompany(companyId, editData.location_head_ids || [])
                .then(function () {
                    $('#country').selectpicker('val', editData.country);
                });
        }

        $('#owner_company_id').on('changed.bs.select', function () {
            loadLocationHeadsByCompany($(this).val(), []);
        });

        $('#filter_company_id').on('changed.bs.select', function () {
            loadHierarchyClients('filter_');
        });

        $('#filter_client_id').on('changed.bs.select', function () {
            loadHierarchyProjects('filter_').then(function () {
                return loadHierarchyDepartments('filter_');
            }).then(function () {
                return loadHierarchyEmployees('filter_');
            });
        });

        $('#filter_project_id, #filter_department_id').on('changed.bs.select', function () {
            loadHierarchyEmployees('filter_');
        });

        $('#apply_location_filters').on('click', function () {
            $('#location-table').DataTable().ajax.reload();
        });

        function ensureLocationFormSelectpickers() {
            $('#formModal .selectpicker, #location_filter_flow .selectpicker').each(function () {
                if (! $(this).data('selectpicker')) {
                    $(this).selectpicker();
                } else {
                    $(this).selectpicker('refresh');
                }
            });
        }

        $('#formModal').on('shown.bs.modal', function () {
            ensureLocationFormSelectpickers();
            if ($('#action').val() === '{{trans('file.Add')}}') {
                $('#owner_company_id').selectpicker('val', '');
                $('#location_head').prop('disabled', true).empty();
                refreshHierarchySelect($('#location_head'));
                $('#location_head').selectpicker('val', []);
                $('#country').selectpicker('val', '');
                $('#owner_type_hidden').val('company');
            } else if (editLocationData) {
                applyEditLocationOwner(editLocationData);
                editLocationData = null;
            }
        });

        $('#formModal').on('hidden.bs.modal', function () {
            $('#formModal .bootstrap-select').removeClass('open show');
            $('#formModal .dropdown-menu').removeClass('show');
        });

        $('#create_record').on('click', function () {

            $('.modal-title').text("{{__('Add Location')}}");
            $('#action_button').val('{{trans("file.Add")}}');
            $('#action').val('{{trans("file.Add")}}');
            $('#sample_form')[0].reset();
            $('#formModal').modal('show');
        });

        function hidePageLoader() {
            $('#loader').stop(true, true).fadeOut(200);
        }

        $('#sample_form').on('submit', function (event) {
            event.preventDefault();
            $('#owner_type_hidden').val('company');

            if (!$('#owner_company_id').val()) {
                $('#form_result').html('<div class="alert alert-danger">{{ __('Please select a company.') }}</div>').slideDown(300);
                return;
            }

            if ($('#action').val() == '{{trans('file.Add')}}') {
                $.ajax({
                    url: "{{ route('locations.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
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
                            $('#sample_form')[0].reset();
                            $('#location-table').DataTable().ajax.reload(null, false);
                        }
                        $('#form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    },
                    error: function () {
                        $('#form_result').html(
                            '<div class="alert alert-danger">{{ __('Something went wrong. Please try again.') }}</div>'
                        ).slideDown(300).delay(5000).slideUp(300);
                    },
                    complete: hidePageLoader
                })
            }

            if ($('#action').val() == '{{trans('file.Edit')}}') {
                $.ajax({
                    url: "{{ route('locations.update') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
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
                            setTimeout(function () {
                                $('#formModal').modal('hide');
                                $('#location-table').DataTable().ajax.reload(null, false);
                                $('#sample_form')[0].reset();
                            }, 2000);

                        }
                        $('#form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    },
                    error: function () {
                        $('#form_result').html(
                            '<div class="alert alert-danger">{{ __('Something went wrong. Please try again.') }}</div>'
                        ).slideDown(300).delay(5000).slideUp(300);
                    },
                    complete: hidePageLoader
                });
            }
        });


        $(document).on('click', '.assign_shift', function () {
            var id = $(this).attr('id');
            $('#shift_assign_result').html('');
            $('#shift_assign_rows').html('<tr><td colspan="3" class="text-center">{{ __('Loading...') }}</td></tr>');

            $.ajax({
                url: "{{ url('/organization/locations') }}/" + id + "/shift-assignment",
                dataType: "json",
                success: function (res) {
                    $('#shift_location_id').val(res.location.id);
                    $('#shift_location_name').text(res.location.location_name);
                    $('#shift_employee_summary').text(
                        '{{ __('Total active employees at this location') }}: ' + (res.total_employees || 0)
                    );

                    var rows = '';
                    (res.companies || []).forEach(function (company, index) {
                        var shiftOptions = '<option value="">{{ __('Select Shift') }}</option>';
                        (company.shifts || []).forEach(function (shift) {
                            shiftOptions += '<option value="' + shift.id + '">' + shift.shift_name + '</option>';
                        });

                        rows += '<tr>' +
                            '<td>' + company.company_name + '</td>' +
                            '<td>' + (company.employee_count || 0) + '</td>' +
                            '<td>' +
                            '<select class="form-control shift-company-select" ' +
                            'name="shifts[' + index + '][office_shift_id]" ' +
                            'data-company-id="' + company.id + '" required>' +
                            shiftOptions +
                            '</select>' +
                            '<input type="hidden" name="shifts[' + index + '][company_id]" value="' + company.id + '">' +
                            '</td>' +
                            '</tr>';
                    });

                    if (!rows) {
                        rows = '<tr><td colspan="3" class="text-center text-danger">{{ __('No companies linked to this location.') }}</td></tr>';
                        $('#shift_assign_submit').prop('disabled', true);
                    } else {
                        $('#shift_assign_submit').prop('disabled', false);
                    }

                    $('#shift_assign_rows').html(rows);
                    $('#shiftAssignModal').modal('show');
                },
                error: function () {
                    $('#shift_assign_rows').html(
                        '<tr><td colspan="3" class="text-center text-danger">{{ __('Unable to load shift assignment data.') }}</td></tr>'
                    );
                    $('#shiftAssignModal').modal('show');
                }
            });
        });

        $('#shift_assign_form').on('submit', function (event) {
            event.preventDefault();

            var shifts = [];
            $('#shift_assign_rows .shift-company-select').each(function () {
                var shiftId = $(this).val();
                var companyId = $(this).data('company-id');
                if (shiftId) {
                    shifts.push({
                        company_id: companyId,
                        office_shift_id: shiftId
                    });
                }
            });

            if (!shifts.length) {
                $('#shift_assign_result').html(
                    '<div class="alert alert-danger">{{ __('Please select at least one shift.') }}</div>'
                ).slideDown(200);
                return;
            }

            $.ajax({
                url: "{{ route('locations.assign_shift') }}",
                method: "POST",
                data: {
                    _token: $('input[name="_token"]').val(),
                    location_id: $('#shift_location_id').val(),
                    shifts: shifts
                },
                dataType: "json",
                success: function (data) {
                    var html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        (data.errors || []).forEach(function (error) {
                            html += '<p>' + error + '</p>';
                        });
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                        setTimeout(function () {
                            $('#shiftAssignModal').modal('hide');
                        }, 1500);
                    }
                    $('#shift_assign_result').html(html).slideDown(200).delay(5000).slideUp(200);
                },
                complete: hidePageLoader
            });
        });

        $(document).on('click', '.edit', function () {

            var id = $(this).attr('id');
            $('#form_result').html('');

            var target = "{{ url('/organization/locations/edit')}}/" + id;


            $.ajax({
                url: target,
                dataType: "json",
                success: function (html) {


                    $('#location_name').val(html.data.location_name);
                    $('#address1').val(html.data.address1);
                    $('#address2').val(html.data.address2);
                    $('#city').val(html.data.city);
                    $('#state').val(html.data.state);
                    $('#zip').val(html.data.zip);
                    $('#latitude').val(html.data.latitude);
                    $('#longitude').val(html.data.longitude);
                    $('#max_radius').val(html.data.max_radius);

                    editLocationData = {
                        owner_type: html.owner_type,
                        owner_company_id: html.owner_company_id,
                        client_id: html.client_id,
                        location_head_ids: html.location_head_ids || [],
                        country: html.data.country
                    };

                    $('#hidden_id').val(html.data.id);
                    $('.modal-title').text('{{trans('file.Edit')}}');
                    $('#action_button').val('{{trans('file.Edit')}}');
                    $('#action').val('{{trans('file.Edit')}}');
                    $('#formModal').modal('show');
                }
            })
        });


        var delete_id;

        $(document).on('click', '.delete', function () {
            delete_id = $(this).attr('id');
            $('#confirmModal').modal('show');
            $('.modal-title').text('{{__('DELETE Record')}}');
            $('#ok_button').text('{{trans('file.OK')}}');

        });


        $(document).on('click', '#bulk_delete', function () {

            var id = [];
            let table = $('#location-table').DataTable();
            id = table.rows({selected: true}).ids().toArray();
            if (id.length > 0) {
                if (confirm('{{__('Delete Selection',['key'=>trans('file.Location')])}}')) {
                    $.ajax({
                        url: '{{route('mass_delete_location')}}',
                        method: 'POST',
                        data: {
                            locationIdArray: id
                        },
                        success: function (data) {
                            let html = '';
                            if (data.success) {
                                html = '<div class="alert alert-success">' + data.success + '</div>';
                            }
                            if (data.error) {
                                html = '<div class="alert alert-danger">' + data.error + '</div>';
                            }
                            table.ajax.reload();
                            table.rows('.selected').deselect();
                            if (data.errors) {
                                html = '<div class="alert alert-danger">' + data.error + '</div>';
                            }
                            $('#general_result').html(html).slideDown(300).delay(5000).slideUp(300);

                        }

                    });
                }
            } else {
                alert('{{__('Please select atleast one checkbox')}}');
            }
        });


        $('#close').on('click', function () {
            $('#sample_form')[0].reset();
        });

        $('#ok_button').on('click', function () {
            var target = "{{ url('/organization/locations/delete')}}/" + delete_id;
            $.ajax({
                url: target,
                beforeSend: function () {
                    $('#ok_button').text('{{trans('file.Deleting...')}}');
                },
                success: function (data) {
                    let html = '';
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                    }
                    if (data.error) {
                        html = '<div class="alert alert-danger">' + data.error + '</div>';
                    }
                    setTimeout(function () {
                        $('#general_result').html(html).slideDown(300).delay(5000).slideUp(300);
                        $('#confirmModal').modal('hide');
                        $('#location-table').DataTable().ajax.reload();
                    }, 2000);
                }
            })
        });

    })(jQuery);
</script>
@endpush
