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
                    <p class="text-muted small">{{ __('You can update location address and GPS settings for your center. Client and location head are managed by admin.') }}</p>
                @endif
                <form method="post" id="sample_form" class="form-horizontal">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{ trans('file.Location') }} *</label>
                            <input type="text" name="location_name" id="location_name" required class="form-control"
                                   placeholder="{{ __('Unique Value', ['key' => trans('file.Location')]) }}">
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

<div id="attendanceTypeModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Change Attendance Type') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <span id="attendance_type_result"></span>
                <p class="mb-2">
                    <strong>{{ trans('file.Location') }}:</strong>
                    <span id="attendance_location_name">-</span>
                </p>
                <p class="text-muted mb-3" id="attendance_employee_summary"></p>
                <form id="attendance_type_form">
                    @csrf
                    <input type="hidden" name="location_id" id="attendance_location_id">
                    <div class="form-group">
                        <label class="text-bold">{{ __('Attendance Type') }} <span class="text-danger">*</span></label>
                        <select name="attendance_type" id="attendance_type_select" class="form-control" required>
                            <option value="">{{ __('Select Attendance Type...') }}</option>
                            <option value="general">{{ __('General') }}</option>
                            <option value="location_based">{{ __('Location Based') }}</option>
                        </select>
                    </div>
                    <small class="text-muted d-block">
                        {{ __('All active employees assigned to this location will be updated to the selected attendance type.') }}
                    </small>
                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-warning" id="attendance_type_submit">
                            {{ __('Update Attendance Type') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
