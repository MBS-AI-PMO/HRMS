<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $general_setting->site_title ?? 'HRMS' }} — {{ $selectedCompany->company_name ?? __('Employee Registration') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap-select.min.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --reg-primary: #6c5ce7;
            --reg-primary-dark: #5b4cdb;
            --reg-bg: #f0f2f8;
            --reg-card: #ffffff;
            --reg-text: #1e293b;
            --reg-muted: #64748b;
            --reg-border: #e2e8f0;
            --reg-radius: 12px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--reg-bg);
            min-height: 100vh;
            margin: 0;
            color: var(--reg-text);
        }
        .reg-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .reg-card {
            width: 100%;
            max-width: 820px;
            background: var(--reg-card);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,.06), 0 20px 40px -12px rgba(108,92,231,.15);
            overflow: hidden;
            border: 1px solid var(--reg-border);
        }
        .reg-header {
            background: linear-gradient(135deg, var(--reg-primary) 0%, var(--reg-primary-dark) 100%);
            color: #fff;
            padding: 2rem 2rem 1.75rem;
            text-align: center;
        }
        .reg-header .company-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.35rem;
            letter-spacing: -0.02em;
        }
        .reg-header .page-title {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.95;
            margin: 0 0 0.5rem;
        }
        .reg-header .intro {
            font-size: 0.9rem;
            opacity: 0.88;
            margin: 0;
            line-height: 1.5;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }
        .reg-body { padding: 2rem; }
        .form-section {
            margin-bottom: 1.75rem;
        }
        .form-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--reg-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(108, 92, 231, 0.15);
        }
        .reg-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--reg-text);
            margin-bottom: 0.35rem;
            display: block;
        }
        .reg-label .req { color: #ef4444; }
        .form-control {
            border-radius: 8px;
            border: 1px solid var(--reg-border);
            padding: 0.55rem 0.85rem;
            font-size: 0.9375rem;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus {
            border-color: var(--reg-primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.15);
        }
        .field-block { display: none; }
        .field-block.visible { display: block; }
        .org-field.visible-org { display: block !important; }
        .org-field { display: none; }
        .hint-text {
            font-size: 0.75rem;
            color: var(--reg-muted);
            margin-top: 0.25rem;
        }
        .btn-register {
            background: linear-gradient(135deg, var(--reg-primary) 0%, var(--reg-primary-dark) 100%);
            border: none;
            border-radius: 10px;
            padding: 0.85rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            transition: transform .15s, box-shadow .15s;
        }
        .btn-register:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(108, 92, 231, 0.35);
            background: linear-gradient(135deg, var(--reg-primary-dark) 0%, #4a3ec9 100%);
        }
        .btn-register:disabled {
            opacity: 0.65;
        }
        .login-link {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.875rem;
        }
        .login-link a {
            color: var(--reg-primary);
            font-weight: 500;
            text-decoration: none;
        }
        .login-link a:hover { text-decoration: underline; }
        .alert { border-radius: 8px; font-size: 0.875rem; }
        #config_loading {
            border-radius: 8px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            color: #4338ca;
        }
        .custom-file-label, .form-control-file {
            font-size: 0.875rem;
        }
        .date-picker-wrap .input-group-text {
            background: #f8fafc;
            border-color: var(--reg-border);
            cursor: pointer;
            color: var(--reg-primary);
        }
        .date-picker-wrap .form-control {
            background: #fff;
            cursor: pointer;
        }
        .datepicker {
            z-index: 1060 !important;
        }
        .reg-card { position: relative; }
        .reg-loader-overlay {
            display: none;
            position: absolute;
            inset: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(2px);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            border-radius: 16px;
        }
        .reg-loader-overlay.active {
            display: flex;
        }
        .reg-loader-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(108, 92, 231, 0.2);
            border-top-color: var(--reg-primary);
            border-radius: 50%;
            animation: reg-spin 0.75s linear infinite;
        }
        @keyframes reg-spin {
            to { transform: rotate(360deg); }
        }
        .reg-loader-text {
            margin-top: 1rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--reg-primary);
        }
        .btn-register .btn-spinner {
            display: none;
            width: 1.1rem;
            height: 1.1rem;
            border: 2px solid rgba(255,255,255,.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: reg-spin 0.65s linear infinite;
            vertical-align: middle;
            margin-right: 0.5rem;
        }
        .btn-register.is-loading .btn-spinner { display: inline-block; }
        .btn-register.is-loading .btn-label { opacity: 0.9; }
    </style>
</head>
<body>
<div class="reg-page">
    <div class="reg-card">
        <div id="reg_loader" class="reg-loader-overlay" aria-live="polite" aria-busy="true">
            <div class="reg-loader-spinner"></div>
            <p class="reg-loader-text" id="reg_loader_text">{{ __('Please wait...') }}</p>
        </div>
        <div class="reg-header">
            <h1 class="company-name" id="page_heading">
                {{ $selectedCompany?->company_name ?? __('Employee Registration') }}
            </h1>
            <p class="page-title" id="page_subtitle">{{ $registrationSetting?->page_title ?? __('Employee Registration') }}</p>
            <p class="intro" id="intro_text">{{ $registrationSetting?->intro_text ?? '' }}</p>
        </div>

        <div class="reg-body">
            @if ($companies->isEmpty())
                <div class="alert alert-warning">{{ __('No company has public registration enabled. Please contact administrator.') }}</div>
                <div class="login-link"><a href="{{ route('login') }}">{{ __('Back to Login') }}</a></div>
            @else
                @if (empty($selectedCompany))
                    <div id="config_loading" class="alert alert-info mb-3">{{ __('Loading form...') }}</div>
                @endif
                <div id="form_result"></div>

                <form id="public_registration_form" enctype="multipart/form-data">
                    @csrf
                    @if (empty($selectedCompany))
                        <div class="form-section">
                            <div class="form-section-title">{{ trans('file.Company') }}</div>
                            <div class="form-group">
                                <label class="reg-label">{{ trans('file.Company') }} <span class="req">*</span></label>
                                <select name="company_id" id="company_id" class="form-control selectpicker" required data-live-search="true">
                                    <option value="">{{ __('Select Company') }}</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="company_id" id="company_id" value="{{ $selectedCompany->id }}">
                    @endif

                    <div class="form-section">
                        <div class="form-section-title">{{ __('Personal Information') }}</div>
                        <div class="row">
                            @php
                                $personalFields = ['first_name', 'last_name', 'email', 'contact_no', 'cnic', 'date_of_birth', 'gender'];
                                $fieldLabels = [
                                    'first_name' => __('First Name'),
                                    'last_name' => __('Last Name'),
                                    'email' => trans('file.Email'),
                                    'contact_no' => trans('file.Phone'),
                                    'cnic' => __('CNIC'),
                                    'date_of_birth' => __('Date Of Birth'),
                                    'gender' => trans('file.Gender'),
                                ];
                            @endphp
                            @foreach ($personalFields as $fieldKey)
                                @php
                                    $cfg = $formFields[$fieldKey] ?? ['enabled' => false, 'required' => false];
                                    $showField = !empty($selectedCompany) ? !empty($cfg['enabled']) : false;
                                @endphp
                                <div class="col-md-6 form-group field-block {{ $showField ? 'visible' : '' }}" data-field="{{ $fieldKey }}">
                                    <label class="reg-label">{{ $fieldLabels[$fieldKey] }} @if(!empty($cfg['required']))<span class="req">*</span>@endif</label>
                                    @if ($fieldKey === 'gender')
                                        <select name="gender" class="form-control" @if(!empty($cfg['required'])) required @endif>
                                            <option value="">{{ __('Select gender') }}</option>
                                            <option value="Male">{{ trans('file.Male') }}</option>
                                            <option value="Female">{{ trans('file.Female') }}</option>
                                            <option value="Other">{{ trans('file.Other') }}</option>
                                        </select>
                                    @elseif ($fieldKey === 'email')
                                        <input type="email" name="email" class="form-control" placeholder="name@company.com" @if(!empty($cfg['required'])) required @endif>
                                        <span class="hint-text">{{ __('Login password will be sent to this email.') }}</span>
                                    @elseif ($fieldKey === 'cnic')
                                        <input type="text" name="cnic" id="cnic" class="form-control cnic-input" placeholder="35201-1234567-1" maxlength="15" autocomplete="off" inputmode="numeric" @if(!empty($cfg['required'])) required @endif>
                                        <span class="hint-text">{{ __('Format: 12345-1234567-1') }}</span>
                                    @elseif ($fieldKey === 'date_of_birth')
                                        <div class="input-group date-picker-wrap">
                                            <input type="text" name="date_of_birth" id="date_of_birth" class="form-control date-dob" placeholder="{{ env('Date_Format_JS', 'dd-mm-yyyy') }}" autocomplete="off" readonly @if(!empty($cfg['required'])) required @endif>
                                            <div class="input-group-append">
                                                <span class="input-group-text" data-toggle-dob><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg></span>
                                            </div>
                                        </div>
                                    @else
                                        <input type="{{ $fieldKey === 'contact_no' ? 'tel' : 'text' }}" name="{{ $fieldKey }}" class="form-control" autocomplete="off" @if(!empty($cfg['required'])) required @endif>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">{{ __('Work Details') }}</div>
                        <div class="row">
                            <div class="col-md-6 form-group org-field {{ (!empty($selectedCompany) && $orgSettings['show_department']) ? 'visible-org' : '' }}" id="department_wrap">
                                <label class="reg-label">{{ trans('file.Department') }} <span class="req">*</span></label>
                                <select name="department_id" id="department_id" class="form-control" @if(!empty($selectedCompany) && $orgSettings['show_department']) required @endif>
                                    <option value="">{{ __('Select department') }}</option>
                                    @foreach ($departments ?? [] as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group org-field {{ (!empty($selectedCompany) && $orgSettings['show_designation']) ? 'visible-org' : '' }}" id="designation_wrap">
                                <label class="reg-label">{{ trans('file.Designation') }} <span class="req">*</span></label>
                                <select name="designation_id" id="designation_id" class="form-control" @if(!empty($selectedCompany) && $orgSettings['show_designation']) required @endif>
                                    <option value="">{{ __('Select department first') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group org-field {{ (!empty($selectedCompany) && $orgSettings['show_shift']) ? 'visible-org' : '' }}" id="shift_wrap">
                                <label class="reg-label">{{ trans('file.Office_Shift') }} <span class="req">*</span></label>
                                <select name="office_shift_id" id="office_shift_id" class="form-control" @if($orgSettings['show_shift']) required @endif>
                                    <option value="">{{ __('Select shift') }}</option>
                                    @foreach ($officeShifts ?? [] as $shift)
                                        <option value="{{ $shift->id }}">{{ $shift->shift_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @php
                                $workFields = ['joining_date'];
                            @endphp
                            @foreach ($workFields as $fieldKey)
                                @php
                                    $cfg = $formFields[$fieldKey] ?? ['enabled' => false, 'required' => false];
                                    $showField = !empty($selectedCompany) ? !empty($cfg['enabled']) : false;
                                @endphp
                                <div class="col-md-6 form-group field-block {{ $showField ? 'visible' : '' }}" data-field="{{ $fieldKey }}">
                                    <label class="reg-label">{{ __('Date Of Joining') }} @if(!empty($cfg['required']))<span class="req">*</span>@endif</label>
                                    <div class="input-group date-picker-wrap">
                                        <input type="text" name="joining_date" id="joining_date" class="form-control date-join" placeholder="{{ env('Date_Format_JS', 'dd-mm-yyyy') }}" autocomplete="off" readonly @if(!empty($cfg['required'])) required @endif>
                                        <div class="input-group-append">
                                            <span class="input-group-text" data-toggle-join><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg></span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">{{ __('Account') }}</div>
                        <div class="row">
                            @php $cfgUser = $formFields['username'] ?? ['enabled' => true, 'required' => true]; @endphp
                            <div class="col-md-6 form-group field-block {{ !empty($selectedCompany) && !empty($cfgUser['enabled']) ? 'visible' : '' }}" data-field="username">
                                <label class="reg-label">{{ trans('file.Username') }} @if(!empty($cfgUser['required']))<span class="req">*</span>@endif</label>
                                <input type="text" name="username" class="form-control" placeholder="{{ __('Choose a unique username') }}" @if(!empty($cfgUser['required'])) required @endif>
                            </div>
                            @php $cfgPhoto = $formFields['profile_photo'] ?? ['enabled' => true, 'required' => false]; @endphp
                            <div class="col-md-6 form-group field-block {{ !empty($selectedCompany) && !empty($cfgPhoto['enabled']) ? 'visible' : '' }}" data-field="profile_photo">
                                <label class="reg-label">{{ __('Profile Photo') }} @if(!empty($cfgPhoto['required']))<span class="req">*</span>@endif</label>
                                <input type="file" name="profile_photo" class="form-control" accept="image/jpeg,image/png,image/jpg,image/gif" @if(!empty($cfgPhoto['required'])) required @endif>
                                <span class="hint-text">{{ __('JPG, PNG — max 10MB') }}</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-register btn-block mt-2" id="submit_btn" @if(empty($selectedCompany)) disabled @endif>
                        <span class="btn-spinner" aria-hidden="true"></span>
                        <span class="btn-label">{{ __('Complete Registration') }}</span>
                    </button>
                    <div class="login-link">
                        {{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('vendor/jquery/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap-select.min.js') }}"></script>
<script>
(function () {
    const preselectedCompanyId = @json($selectedCompany->id ?? null);
    const preselectedCompanySlug = @json($selectedCompany->registration_slug ?? null);
    const preselectedCompanyName = @json($selectedCompany->company_name ?? null);
    const orgSettings = @json($orgSettings ?? ['show_department' => true, 'show_designation' => true, 'show_shift' => false]);
    const configUrlBase = @json(url('register/employee/config'));
    const loaderMessages = {
        default: @json(__('Please wait...')),
        config: @json(__('Loading form...')),
        submit: @json(__('Submitting registration...'))
    };

    function setRegLoading(active, message) {
        const $overlay = $('#reg_loader');
        const $btn = $('#submit_btn');
        if (active) {
            $('#reg_loader_text').text(message || loaderMessages.default);
            $overlay.addClass('active');
            $btn.addClass('is-loading').prop('disabled', true);
            $('#public_registration_form :input').prop('disabled', true);
        } else {
            $overlay.removeClass('active');
            $btn.removeClass('is-loading');
            $('#public_registration_form :input').prop('disabled', false);
            if (!preselectedCompanyId && !$('#company_id').val()) {
                $btn.prop('disabled', true);
            } else if ($('#public_registration_form').is(':visible')) {
                $btn.prop('disabled', false);
            }
        }
    }

    const dateFormat = @json(env('Date_Format_JS', 'dd-mm-yyyy'));

    function initDatePickers() {
        const dobOpts = {
            format: dateFormat,
            autoclose: true,
            todayHighlight: true,
            endDate: new Date(),
            orientation: 'bottom auto',
            clearBtn: false
        };
        const joinOpts = {
            format: dateFormat,
            autoclose: true,
            todayHighlight: true,
            orientation: 'bottom auto',
            clearBtn: false
        };

        $('input.date-dob:visible').each(function () {
            const $el = $(this);
            if ($el.data('datepicker')) {
                $el.datepicker('destroy');
            }
            $el.datepicker(dobOpts);
        });

        $('input.date-join:visible').each(function () {
            const $el = $(this);
            if ($el.data('datepicker')) {
                $el.datepicker('destroy');
            }
            $el.datepicker(joinOpts);
        });
    }

    $(document).on('click', '[data-toggle-dob]', function () {
        $('input.date-dob').datepicker('show');
    });
    $(document).on('click', '[data-toggle-join]', function () {
        $('input.date-join').datepicker('show');
    });

    initDatePickers();

    function formatCnicValue(value) {
        const digits = (value || '').replace(/\D/g, '').slice(0, 13);
        if (digits.length <= 5) return digits;
        if (digits.length <= 12) return digits.slice(0, 5) + '-' + digits.slice(5);
        return digits.slice(0, 5) + '-' + digits.slice(5, 12) + '-' + digits.slice(12);
    }

    $(document).on('input', '.cnic-input', function () {
        const pos = this.selectionStart;
        const formatted = formatCnicValue(this.value);
        this.value = formatted;
        this.setSelectionRange(formatted.length, formatted.length);
    });

    function applyFieldConfig(fields) {
        $('.field-block').removeClass('visible').find('input,select').not('#department_id, #designation_id, #office_shift_id').prop('required', false);
        $('.field-block .req').hide();
        Object.keys(fields || {}).forEach(function (key) {
            const cfg = fields[key];
            if (!cfg.enabled) return;
            const block = $('.field-block[data-field="' + key + '"]');
            block.addClass('visible');
            if (cfg.required) {
                block.find('input,select').prop('required', true);
                block.find('.req').show();
            }
        });
        initDatePickers();
    }

    function updateHeading(res) {
        const s = res.setting || {};
        if (preselectedCompanyId) {
            $('#page_heading').text(res.company_name || preselectedCompanyName);
            $('#page_subtitle').text(s.page_title || '{{ __('Employee Registration') }}');
        } else {
            $('#page_heading').text(s.page_title || '{{ __('Employee Registration') }}');
            $('#page_subtitle').text(res.company_name || '');
        }
        $('#intro_text').text(s.intro_text || '');
    }

    function loadDepartments(companyId, selectedId) {
        const $dept = $('#department_id');
        $dept.prop('disabled', true);
        return $.post('{{ route('employee.register.departments') }}', {
            _token: '{{ csrf_token() }}',
            company_id: companyId
        }).done(function (html) {
            $dept.html(html);
            if (selectedId) {
                $dept.val(selectedId);
            }
        }).always(function () {
            $dept.prop('disabled', false);
        });
    }

    function loadDesignations(departmentId, selectedId) {
        const $desig = $('#designation_id');
        if (!departmentId) {
            $desig.html('<option value="">{{ __('Select department first') }}</option>');
            return $.Deferred().resolve().promise();
        }
        $desig.prop('disabled', true);
        return $.post('{{ route('employee.register.designations') }}', {
            _token: '{{ csrf_token() }}',
            department_id: departmentId
        }).done(function (html) {
            $desig.html(html);
            if (selectedId) {
                $desig.val(selectedId);
            }
        }).always(function () {
            $desig.prop('disabled', false);
        });
    }

    function syncOrgFields(setting) {
        const companyId = setting.company_id;

        $('#department_wrap').addClass('visible-org').show();
        $('#department_id').prop('required', true);
        $('#designation_wrap').addClass('visible-org').show();
        $('#designation_id').prop('required', true);

        if (orgSettings.show_shift || setting.allow_shift_selection) {
            $('#shift_wrap').addClass('visible-org').show();
            $('#office_shift_id').prop('required', true);
        } else {
            $('#shift_wrap').removeClass('visible-org').hide();
            $('#office_shift_id').prop('required', false);
        }

        return loadDepartments(companyId, setting.default_department_id || null).then(function () {
            const deptId = $('#department_id').val() || setting.default_department_id;
            if (deptId) {
                return loadDesignations(deptId, setting.default_designation_id || null);
            }
        });
    }

    $('#department_id').on('change', function () {
        loadDesignations($(this).val(), null);
    });

    function loadCompanyConfig(companyKey) {
        if (!companyKey) {
            $('#submit_btn').prop('disabled', true);
            return;
        }
        if ($('#config_loading').length) {
            $('#config_loading').hide();
        }
        setRegLoading(true, loaderMessages.config);

        $.get(configUrlBase + '/' + companyKey, function (res) {
            updateHeading(res);
            applyFieldConfig(res.form_fields);
            $.when(syncOrgFields(res.setting)).always(function () {
                setRegLoading(false);
                if ($('#public_registration_form').is(':visible') && ($('#company_id').val() || preselectedCompanyId)) {
                    $('#submit_btn').prop('disabled', false);
                }
            });
        }).fail(function (xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : '{{ __('Unable to load registration settings.') }}';
            $('#form_result').html('<div class="alert alert-danger">' + msg + '</div>');
            $('#submit_btn').prop('disabled', true);
            setRegLoading(false);
        });
    }

    $('#company_id').on('change', function () {
        const id = $(this).val();
        if (!id) return;
        loadCompanyConfig(id);
    });

    if (preselectedCompanySlug) {
        loadCompanyConfig(preselectedCompanySlug);
    } else if (preselectedCompanyId) {
        loadCompanyConfig(preselectedCompanyId);
        syncOrgFields({
            company_id: preselectedCompanyId,
            default_department_id: null,
            default_designation_id: null
        });
    }

    $('#public_registration_form').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        $('#form_result').empty();
        setRegLoading(true, loaderMessages.submit);
        $.ajax({
            url: '{{ route('employee.register.store') }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                setRegLoading(true, '{{ __('Redirecting to login page...') }}');
                let message = data.success || '{{ __('Registration successful.') }}';
                if (data.staff_id) {
                    message += ' {{ __('Staff Id') }}: ' + data.staff_id;
                }

                let html = '<div class="alert alert-success text-center py-4 mb-0">' +
                    '<strong>{{ __('Success!') }}</strong><br>' + message +
                    '<br><small class="d-block mt-3 text-muted">{{ __('Redirecting to login page...') }}</small></div>';
                $('#form_result').html(html);
                $('#public_registration_form').hide();

                try {
                    sessionStorage.setItem('registration_success', message);
                } catch (e) {}

                setTimeout(function () {
                    window.location.href = @json(route('login'));
                }, 2500);
            },
            error: function (xhr) {
                setRegLoading(false);
                const data = xhr.responseJSON || {};
                let messages = [];
                if (data.errors && data.errors.length) {
                    messages = data.errors;
                } else if (data.error) {
                    messages = [data.error];
                } else if (data.message) {
                    messages = [data.message];
                } else {
                    messages = ['{{ __('Registration failed') }}' + (xhr.status ? ' (HTTP ' + xhr.status + ')' : '')];
                }
                let html = '<div class="alert alert-danger"><ul>';
                messages.forEach(function (err) {
                    html += '<li>' + err + '</li>';
                });
                html += '</ul></div>';
                $('#form_result').html(html);
            },
            complete: function (xhr) {
                if (xhr.status >= 400) {
                    setRegLoading(false);
                }
            }
        });
    });
})();
</script>
</body>
</html>
