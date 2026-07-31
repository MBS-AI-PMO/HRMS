@extends('layout.main')
@section('content')
    <section class="forms">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">
                        @if (!empty($isNew))
                            {{ __('Create Client Registration Link') }}
                        @else
                            {{ __('Edit Client Registration') }}
                        @endif
                    </h4>
                    <a href="{{ route('client_registration_settings.index') }}" class="btn btn-secondary btn-sm">
                        <i class="dripicons-arrow-thin-left"></i> {{ __('Back to List') }}
                    </a>
                </div>
                <div class="card-body">
                    @if (empty($isNew))
                        <div class="alert alert-light border">
                            <strong>{{ __('Public registration URL') }}:</strong><br>
                            <a href="{{ $setting->registrationUrl() }}" target="_blank">{{ $setting->registrationUrl() }}</a>
                            <p class="small text-muted mb-0 mt-1">{{ __('Share this link. Client and project assignment are pre-configured.') }}</p>
                        </div>
                    @endif

                    <span id="settings_result"></span>

                    <form id="client_registration_settings_form">
                        @csrf
                        <input type="hidden" id="config_setting_id" value="{{ $setting->id ?? '' }}">

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>{{ trans('file.Client') }} <span class="text-danger">*</span></label>
                                <select name="client_id" id="client_id" class="form-control selectpicker" data-live-search="true" required>
                                    <option value="">{{ __('Select Client') }}</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}"
                                            {{ (int) ($setting->client_id ?? 0) === (int) $client->id ? 'selected' : '' }}>
                                            {{ trim(($client->company_name ?: '').' '.($client->first_name ?? '').' '.($client->last_name ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Link Label') }}</label>
                                <input type="text" name="label" id="label" class="form-control"
                                    value="{{ $setting->label ?? '' }}"
                                    placeholder="{{ __('e.g. Project Alpha Team Registration') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ trans('file.Projects') }} <span class="text-danger">*</span></label>
                            <select name="project_ids[]" id="project_ids" class="form-control selectpicker" multiple data-live-search="true" title="{{ __('Select project(s)') }}">
                            </select>
                            <small class="text-muted">{{ __('Employee will be assigned to these projects after registration.') }}</small>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="is_enabled" id="is_enabled" value="1">
                                        {{ __('Enable public registration for this link') }}
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{ __('Page Title') }}</label>
                                <input type="text" name="page_title" id="page_title" class="form-control" placeholder="{{ __('Client Employee Registration') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Default Role') }}</label>
                                <select name="default_role_users_id" id="default_role_users_id" class="form-control selectpicker">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 form-group">
                                <label>{{ __('Intro Text') }}</label>
                                <textarea name="intro_text" id="intro_text" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>{{ __('Success Message') }}</label>
                                <input type="text" name="success_message" id="success_message" class="form-control">
                            </div>

                        <div class="col-md-4 form-group">
                            <label>
                                <input type="checkbox" name="auto_approve" id="auto_approve" value="1"
                                    {{ !empty($isNew) || !empty($setting->auto_approve) ? 'checked' : '' }}>
                                {{ __('Auto approve (account active immediately)') }}
                            </label>
                        </div>
                            <div class="col-md-4 form-group">
                                <label>{{ __('Default Attendance Type') }}</label>
                                <select name="default_attendance_type" id="default_attendance_type" class="form-control">
                                    <option value="general">{{ __('General') }}</option>
                                    <option value="location_based">{{ __('Location Based') }}</option>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h5>{{ __('Organization defaults') }}</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <label><input type="checkbox" name="allow_department_selection" id="allow_department_selection" value="1"> {{ __('Let applicant choose department') }}</label>
                                <select name="default_department_id" id="default_department_id" class="form-control mt-2">
                                    <option value="">{{ __('Default Department') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label><input type="checkbox" name="allow_designation_selection" id="allow_designation_selection" value="1"> {{ __('Let applicant choose designation') }}</label>
                                <select name="default_designation_id" id="default_designation_id" class="form-control mt-2">
                                    <option value="">{{ __('Default Designation') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label><input type="checkbox" name="allow_shift_selection" id="allow_shift_selection" value="1"> {{ __('Let applicant choose office shift') }}</label>
                                <select name="default_office_shift_id" id="default_office_shift_id" class="form-control mt-2">
                                    <option value="">{{ __('Default Office Shift') }}</option>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h5>{{ __('Form fields') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('Field') }}</th>
                                        <th>{{ __('Show on form') }}</th>
                                        <th>{{ __('Required') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $labels = [
                                            'first_name' => __('First Name'),
                                            'last_name' => __('Last Name'),
                                            'email' => trans('file.Email'),
                                            'contact_no' => trans('file.Phone'),
                                            'cnic' => __('CNIC'),
                                            'date_of_birth' => __('Date Of Birth'),
                                            'gender' => trans('file.Gender'),
                                            'username' => trans('file.Username'),
                                            'joining_date' => __('Date Of Joining'),
                                            'profile_photo' => __('Image'),
                                        ];
                                    @endphp
                                    @foreach ($labels as $key => $label)
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td><input type="checkbox" name="form_fields[{{ $key }}][enabled]" value="1" class="field-enabled" data-field="{{ $key }}"></td>
                                            <td><input type="checkbox" name="form_fields[{{ $key }}][required]" value="1" class="field-required" data-field="{{ $key }}"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
                        <a href="{{ route('client_registration_settings.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script type="text/javascript">
    (function () {
        const settingId = $('#config_setting_id').val();
        const isNew = @json(!empty($isNew));
        const saveUrl = isNew
            ? "{{ route('client_registration_settings.store') }}"
            : "{{ route('client_registration_settings.update', ['id' => $setting->id ?? 0]) }}";
        const dataUrl = !isNew && settingId
            ? "{{ route('client_registration_settings.data', ['id' => $setting->id ?? 0]) }}"
            : null;
        const clientDataUrlTemplate = @json(route('client_registration_settings.client_data', ['clientId' => '__CLIENT__']));
        const preselectedProjectIds = @json($setting->resolvedProjectIds() ?? []);

        function refreshProjectPicker(html, selectedIds) {
            const $el = $('#project_ids');
            if ($el.data('selectpicker')) {
                $el.selectpicker('destroy');
            }
            $el.html(html);
            if (selectedIds && selectedIds.length) {
                $el.val(selectedIds.map(String));
            }
            $el.selectpicker({ width: '100%', liveSearch: true, actionsBox: true });
        }

        function populateOrgDropdowns(res, selected) {
            const s = selected || {};
            let deptHtml = '<option value="">{{ __("Default Department") }}</option>';
            (res.departments || []).forEach(function (d) {
                deptHtml += '<option value="' + d.id + '">' + d.department_name + '</option>';
            });
            $('#default_department_id').html(deptHtml).val(s.default_department_id || '');

            let desHtml = '<option value="">{{ __("Default Designation") }}</option>';
            (res.designations || []).forEach(function (d) {
                desHtml += '<option value="' + d.id + '">' + d.designation_name + '</option>';
            });
            $('#default_designation_id').html(desHtml).val(s.default_designation_id || '');

            let shiftHtml = '<option value="">{{ __("Default Office Shift") }}</option>';
            (res.shifts || []).forEach(function (d) {
                shiftHtml += '<option value="' + d.id + '">' + d.shift_name + '</option>';
            });
            $('#default_office_shift_id').html(shiftHtml).val(s.default_office_shift_id || '');
        }

        function loadClientData(clientId, selectedSetting) {
            if (!clientId) {
                refreshProjectPicker('', []);
                populateOrgDropdowns({}, {});
                return;
            }

            $.get(clientDataUrlTemplate.replace('__CLIENT__', clientId), function (res) {
                let projectHtml = '';
                (res.projects || []).forEach(function (p) {
                    projectHtml += '<option value="' + p.id + '">' + p.title + '</option>';
                });
                const projectIds = selectedSetting && selectedSetting.project_ids
                    ? selectedSetting.project_ids
                    : preselectedProjectIds;
                refreshProjectPicker(projectHtml, projectIds);
                populateOrgDropdowns(res, selectedSetting || {});
            });
        }

        function applySettingFields(s, fields) {
            $('#is_enabled').prop('checked', !!s.is_enabled);
            $('#label').val(s.label || '');
            $('#page_title').val(s.page_title || '');
            $('#intro_text').val(s.intro_text || '');
            $('#success_message').val(s.success_message || '');
            $('#auto_approve').prop('checked', !!s.auto_approve);
            $('#default_attendance_type').val((s.default_attendance_type === 'ip_based') ? 'location_based' : (s.default_attendance_type || 'location_based'));
            $('#default_role_users_id').val(s.default_role_users_id || 3).selectpicker('refresh');

            $('#allow_department_selection').prop('checked', !!s.allow_department_selection);
            $('#allow_designation_selection').prop('checked', !!s.allow_designation_selection);
            $('#allow_shift_selection').prop('checked', !!s.allow_shift_selection);

            $('.field-enabled, .field-required').prop('checked', false);
            Object.keys(fields || {}).forEach(function (key) {
                if (fields[key].enabled) {
                    $('.field-enabled[data-field="' + key + '"]').prop('checked', true);
                }
                if (fields[key].required) {
                    $('.field-required[data-field="' + key + '"]').prop('checked', true);
                }
            });
        }

        if (dataUrl) {
            $.get(dataUrl, function (res) {
                applySettingFields(res.setting, res.form_fields);
                $('#client_id').val(res.setting.client_id).selectpicker('refresh');
                loadClientData(res.setting.client_id, res.setting);
            });
        } else {
            const initialClientId = $('#client_id').val();
            if (initialClientId) {
                loadClientData(initialClientId, null);
            }
        }

        $('#client_id').on('changed.bs.select', function () {
            loadClientData($(this).val(), null);
        });

        $('#client_registration_settings_form').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: saveUrl,
                method: 'POST',
                data: $(this).serialize(),
                success: function (data) {
                    let html = data.success
                        ? '<div class="alert alert-success">' + data.success + '</div>'
                        : '<div class="alert alert-danger">' + (data.error || '') + '</div>';
                    if (data.errors) {
                        html = '<div class="alert alert-danger"><ul>';
                        data.errors.forEach(function (err) { html += '<li>' + err + '</li>'; });
                        html += '</ul></div>';
                    }
                    $('#settings_result').html(html).slideDown(300).delay(4000).slideUp(300);
                    if (data.redirect) {
                        setTimeout(function () { window.location.href = data.redirect; }, 800);
                    }
                },
                error: function (xhr) {
                    const data = xhr.responseJSON || {};
                    let html = '<div class="alert alert-danger">';
                    (data.errors || [data.error || '{{ __("Save failed") }}']).forEach(function (err) {
                        html += '<p>' + err + '</p>';
                    });
                    html += '</div>';
                    $('#settings_result').html(html).slideDown(300);
                }
            });
        });
    })();
</script>
@endpush
