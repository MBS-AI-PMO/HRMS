@php
    $companyScoped = \App\Support\CompanyScope::applies();
    $singleCompany = $companyScoped && $companies->count() === 1 ? $companies->first() : null;
@endphp

<div id="formModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ $allowCreate ? __('Add Team') : __('Edit Team') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><i class="dripicons-cross"></i></button>
            </div>
            <div class="modal-body">
                <span id="form_result"></span>
                <form method="post" id="sample_form" class="form-horizontal">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{ __('Team Name') }} *</label>
                            <input type="text" name="team_name" id="team_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ trans('file.Company') }} *</label>
                            @if ($singleCompany)
                                <input type="hidden" name="company_id" id="company_id" value="{{ $singleCompany->id }}">
                                <input type="text" class="form-control" value="{{ $singleCompany->company_name }}" readonly>
                            @else
                                <select name="company_id" id="company_id" class="form-control selectpicker"
                                        data-live-search="true" title="{{ __('Select Company') }}" required>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ trans('file.Department') }}</label>
                            <select name="department_id" id="department_id" class="form-control selectpicker"
                                    data-live-search="true" title="{{ __('Select Department') }}">
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ __('Department Head') }} *</label>
                            <select name="department_head_id" id="department_head_id" class="form-control selectpicker"
                                    data-live-search="true" title="{{ __('Select Department Head') }}" required>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ __('Project Manager') }} *</label>
                            <select name="project_manager_id" id="project_manager_id" class="form-control selectpicker"
                                    data-live-search="true" title="{{ __('Select Project Manager') }}" required>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ __('Assistant HR') }}</label>
                            <select name="assistant_hr_id" id="assistant_hr_id" class="form-control selectpicker"
                                    data-live-search="true" title="{{ __('Select Assistant HR') }}">
                            </select>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>{{ __('Team Members') }}</label>
                            <select name="member_ids[]" id="member_ids" class="form-control selectpicker"
                                    data-live-search="true" multiple title="{{ __('Select Team Members') }}">
                            </select>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>{{ __('Description') }}</label>
                            <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-12 form-group text-center">
                            <input type="hidden" name="action" id="action" value="{{ $allowCreate ? trans('file.Add') : __('Edit') }}">
                            <input type="hidden" name="hidden_id" id="hidden_id">
                            <input type="submit" id="action_button" class="btn btn-warning" value="{{ $allowCreate ? trans('file.Add') : __('Edit') }}">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
