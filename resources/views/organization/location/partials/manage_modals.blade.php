<div id="formModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{ __('Edit Location') }}</h5>
                <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><i class="dripicons-cross"></i></button>
            </div>
            <div class="modal-body">
                <span id="form_result"></span>
                @if (!empty($locationHeadManage))
                    <p class="text-muted small">{{ __('You can update location details, assign employees, and office shifts for your center. Company and location head are managed by admin.') }}</p>
                @endif
                <form method="post" id="sample_form" class="form-horizontal">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{ trans('file.Location') }} *</label>
                            <input type="text" name="location_name" id="location_name" required class="form-control"
                                   placeholder="{{ __('Unique Value', ['key' => trans('file.Location')]) }}">
                        </div>

                        <div class="col-md-6 form-group @if(!empty($locationHeadManage)) d-none @endif" id="company_ids_wrap">
                            <label>{{ trans('file.Company') }} *</label>
                            <select name="company_ids[]" id="company_ids" class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains" multiple
                                    title='{{ __('Selecting', ['key' => trans('file.Company')]) }}...'>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 form-group @if(!empty($locationHeadManage)) d-none @endif" id="location_head_wrap">
                            <label>{{ __('Location Head') }}</label>
                            <select name="location_head_ids[]" id="location_head" class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains" multiple
                                    title='{{ __('Selecting', ['key' => trans('file.Employee')]) }}...'>
                            </select>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{ __('Address Line 1') }} *</label>
                            <input type="text" name="address1" id="address1" required class="form-control" placeholder="full address">
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{ __('Address Line 2') }}</label>
                            <input type="text" name="address2" id="address2" class="form-control" placeholder={{ trans('file.Optional') }}>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{ trans('file.City') }}</label>
                            <input type="text" name="city" id="city" class="form-control" placeholder={{ trans('file.Optional') }}>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{ trans('file.State') }}</label>
                            <input type="text" name="state" id="state" class="form-control" placeholder={{ trans('file.Optional') }}>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{ trans('file.Country') }}</label>
                            <select name="country" id="country" class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains"
                                    title='{{ __('Selecting', ['key' => trans('file.Country')]) }}...'>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{ trans('file.ZIP') }}</label>
                            <input type="text" name="zip" id="zip" class="form-control" placeholder={{ trans('file.Optional') }}>
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

                        <div class="col-md-12 form-group">
                            <label class="d-block">{{ __('Assign Employees') }}</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="assign_scope" id="assign_scope_specific" value="specific" checked>
                                <label class="form-check-label" for="assign_scope_specific">{{ __('Specific Employees') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="assign_scope" id="assign_scope_all" value="all">
                                <label class="form-check-label" for="assign_scope_all">{{ __('All Employees of Selected Companies') }}</label>
                            </div>
                        </div>

                        <div class="col-md-12 form-group" id="employee_selection_wrap">
                            <label>{{ trans('file.Employee') }}</label>
                            <select name="employee_ids[]" id="employee_ids" class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains" multiple
                                    title='{{ __('Selecting', ['key' => trans('file.Employee')]) }}...'>
                            </select>
                            <small class="text-muted">{{ __('Employees load from the location companies.') }}</small>
                        </div>

                        <div class="col-md-12 form-group" id="office_shift_wrap">
                            <label>{{ __('Office Shift') }}</label>
                            <small class="text-muted d-block mb-2">
                                {{ __('One shift can be assigned per company for employees at this location.') }}
                            </small>
                            <div id="office_shift_rows" class="border rounded p-2 bg-light text-muted">
                                {{ __('Office shifts will load when you open edit.') }}
                            </div>
                        </div>

                        <div class="form-group" align="center">
                            <input type="hidden" name="action" id="action" value="{{ trans('file.Edit') }}"/>
                            <input type="hidden" name="hidden_id" id="hidden_id"/>
                            <input type="submit" name="action_button" id="action_button" class="btn btn-warning"
                                   value="{{ trans('file.Edit') }}" />
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
                                <th>{{ trans('file.Company') }}</th>
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
