@extends('layout.auth')

@section('title', __('Client Employee Registration'))

@section('brand_headline', $registrationSetting?->page_title ?? __('Client Employee Registration'))
@section('brand_tagline', $registrationSetting?->intro_text ?? __('Register as a client team member.'))

@section('card_eyebrow', __('Registration'))
@section('card_title', $registrationSetting?->page_title ?? __('Client Employee Registration'))
@section('card_subtitle')
    @if ($selectedClient ?? null)
        {{ trim(($selectedClient->company_name ?: '').' '.($selectedClient->first_name ?? '').' '.($selectedClient->last_name ?? '')) }}
        @if (($assignedProjects ?? collect())->isNotEmpty())
            <br><small class="text-muted">{{ __('Projects') }}: {{ $assignedProjects->pluck('title')->implode(', ') }}</small>
        @endif
    @else
        {{ __('This registration link is invalid or disabled.') }}
    @endif
@endsection

@section('content')
    @if (empty($registrationSetting) || empty($selectedClient))
        <a href="{{ route('login') }}" class="auth-btn text-center d-flex align-items-center justify-content-center text-decoration-none">
            {{ __('Back to Login') }}
        </a>
    @else
        <div id="form_result"></div>

        <form id="public_client_registration_form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="registration_setting_id" value="{{ $registrationSetting->id }}">

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
                @endphp
                @if (!empty($cfg['enabled']))
                    <div class="form-group">
                        <label>{{ $fieldLabels[$fieldKey] }} @if(!empty($cfg['required']))<span class="text-danger">*</span>@endif</label>
                        @if ($fieldKey === 'gender')
                            <select name="gender" class="form-control" @if(!empty($cfg['required'])) required @endif>
                                <option value="">{{ __('Select gender') }}</option>
                                <option value="Male">{{ trans('file.Male') }}</option>
                                <option value="Female">{{ trans('file.Female') }}</option>
                                <option value="Other">{{ trans('file.Other') }}</option>
                            </select>
                        @elseif ($fieldKey === 'email')
                            <input type="email" name="email" class="form-control" @if(!empty($cfg['required'])) required @endif>
                        @elseif ($fieldKey === 'cnic')
                            <input type="text" name="cnic" class="form-control cnic-input" placeholder="35201-1234567-1" maxlength="15" @if(!empty($cfg['required'])) required @endif>
                        @elseif ($fieldKey === 'date_of_birth')
                            <input type="text" name="date_of_birth" class="form-control date-dob" placeholder="{{ config('variable.date_format_js', 'dd-mm-yyyy') }}" autocomplete="off" @if(!empty($cfg['required'])) required @endif>
                        @else
                            <input type="{{ $fieldKey === 'contact_no' ? 'tel' : 'text' }}" name="{{ $fieldKey }}" class="form-control" @if(!empty($cfg['required'])) required @endif>
                        @endif
                    </div>
                @endif
            @endforeach

            @if ($orgSettings['show_department'] ?? true)
                <div class="form-group">
                    <label>{{ trans('file.Department') }} <span class="text-danger">*</span></label>
                    <select name="department_id" id="department_id" class="form-control" required>
                        <option value="">{{ __('Select department') }}</option>
                        @foreach ($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}" {{ (int) ($registrationSetting->default_department_id ?? 0) === (int) $dept->id ? 'selected' : '' }}>
                                {{ $dept->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($orgSettings['show_designation'] ?? true)
                <div class="form-group">
                    <label>{{ trans('file.Designation') }} <span class="text-danger">*</span></label>
                    <select name="designation_id" id="designation_id" class="form-control" required>
                        <option value="">{{ __('Select department first') }}</option>
                    </select>
                </div>
            @endif

            @if ($orgSettings['show_shift'] ?? false)
                <div class="form-group">
                    <label>{{ trans('file.Office_Shift') }} <span class="text-danger">*</span></label>
                    <select name="office_shift_id" id="office_shift_id" class="form-control" required>
                        <option value="">{{ __('Select shift') }}</option>
                        @foreach ($officeShifts ?? [] as $shift)
                            <option value="{{ $shift->id }}" {{ (int) ($registrationSetting->default_office_shift_id ?? 0) === (int) $shift->id ? 'selected' : '' }}>
                                {{ $shift->shift_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @php $cfgJoin = $formFields['joining_date'] ?? ['enabled' => true, 'required' => true]; @endphp
            @if (!empty($cfgJoin['enabled']))
                <div class="form-group">
                    <label>{{ __('Date Of Joining') }} @if(!empty($cfgJoin['required']))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="joining_date" class="form-control date-join" placeholder="{{ config('variable.date_format_js', 'dd-mm-yyyy') }}" autocomplete="off" @if(!empty($cfgJoin['required'])) required @endif>
                </div>
            @endif

            @php $cfgUser = $formFields['username'] ?? ['enabled' => true, 'required' => true]; @endphp
            @if (!empty($cfgUser['enabled']))
                <div class="form-group">
                    <label>{{ trans('file.Username') }} @if(!empty($cfgUser['required']))<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="username" class="form-control" @if(!empty($cfgUser['required'])) required @endif>
                </div>
            @endif

            @php $cfgPhoto = $formFields['profile_photo'] ?? ['enabled' => false, 'required' => false]; @endphp
            @if (!empty($cfgPhoto['enabled']))
                <div class="form-group">
                    <label>{{ __('Profile Photo') }} @if(!empty($cfgPhoto['required']))<span class="text-danger">*</span>@endif</label>
                    <input type="file" name="profile_photo" class="form-control" accept="image/jpeg,image/png,image/jpg,image/gif" @if(!empty($cfgPhoto['required'])) required @endif>
                </div>
            @endif

            <button type="submit" class="auth-btn" id="submit_btn">{{ __('Complete Registration') }}</button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}">{{ __('Already have an account? Sign in') }}</a>
        </div>
    @endif
@endsection

@push('scripts')
@if (!empty($registrationSetting) && !empty($selectedClient))
<script src="{{ asset('vendor/jquery/bootstrap-datepicker.min.js') }}"></script>
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap-datepicker.min.css') }}">
<script>
(function () {
    const settingId = @json($registrationSetting->id);
    const defaultDesignationId = @json($registrationSetting->default_designation_id);
    const dateFormat = @json(config('variable.date_format_js', 'dd-mm-yyyy'));

    $('.date-dob, .date-join').datepicker({
        format: dateFormat,
        autoclose: true,
        todayHighlight: true
    });

    function loadDesignations(departmentId, selectedId) {
        if (!departmentId) {
            $('#designation_id').html('<option value="">{{ __("Select department first") }}</option>');
            return;
        }
        $.post('{{ route('client.register.designations') }}', {
            _token: '{{ csrf_token() }}',
            registration_setting_id: settingId,
            department_id: departmentId
        }).done(function (html) {
            $('#designation_id').html(html);
            if (selectedId) {
                $('#designation_id').val(selectedId);
            }
        });
    }

    $('#department_id').on('change', function () {
        loadDesignations($(this).val(), null);
    });

    if ($('#department_id').val()) {
        loadDesignations($('#department_id').val(), defaultDesignationId);
    }

    $(document).on('input', '.cnic-input', function () {
        const digits = (this.value || '').replace(/\D/g, '').slice(0, 13);
        if (digits.length <= 5) {
            this.value = digits;
        } else if (digits.length <= 12) {
            this.value = digits.slice(0, 5) + '-' + digits.slice(5);
        } else {
            this.value = digits.slice(0, 5) + '-' + digits.slice(5, 12) + '-' + digits.slice(12);
        }
    });

    $('#public_client_registration_form').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        $('#submit_btn').prop('disabled', true);
        $('#form_result').empty();

        $.ajax({
            url: '{{ route('client.register.store') }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                $('#form_result').html('<div class="alert alert-success">' + data.success + '</div>');
                $('#public_client_registration_form').hide();
            },
            error: function (xhr) {
                const data = xhr.responseJSON || {};
                let html = '<div class="alert alert-danger"><ul>';
                (data.errors || [data.error || '{{ __("Registration failed") }}']).forEach(function (err) {
                    html += '<li>' + err + '</li>';
                });
                html += '</ul></div>';
                $('#form_result').html(html);
                $('#submit_btn').prop('disabled', false);
            }
        });
    });
})();
</script>
@endif
@endpush
