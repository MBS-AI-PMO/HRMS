@extends('layout.main')
@section('content')
    <style>
        .nav-tabs li a {
            padding: 0.75rem 1.25rem;
        }

        .nav-tabs.vertical li {
            border: 1px solid #ddd;
            display: block;
            width: 100%
        }

        .tab-pane {
            padding: 15px 0
        }
    </style>
    @php
        $profileWorkFieldsReadonly = isset($workFieldsReadonly)
            ? (bool) $workFieldsReadonly
            : auth()->id() === (int) $employee->id &&
                !((int) auth()->user()->role_users_id === 1 && auth()->user()->can('modify-details-employee'));
    @endphp
    <section>

        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    @include('shared.errors')
                    @include('shared.flash_message')
                    <div class="text-center">
                        <h2>{{ $employee->user->username }}</h2>
                    </div>
                    <ul class="nav nav-tabs d-flex flex-wrap" id="profileMainTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#General" role="tab"
                                aria-controls="General" aria-selected="true">{{ trans('file.General') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="leave-tab" data-toggle="tab" href="#Leave" role="tab"
                                aria-controls="Leave" aria-selected="false">{{ trans('file.Leave') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="wfh-tab" data-toggle="tab" href="#WFH" role="tab"
                                aria-controls="WFH" aria-selected="false">WFH</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="employee_award-tab" data-toggle="tab" href="#Employee_award"
                                role="tab" aria-controls="Employee_award"
                                aria-selected="false">{{ trans('file.Award') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="employee_project_task-tab" data-toggle="tab"
                                href="#Employee_project_task" role="tab" aria-controls="Employee_project_task"
                                aria-selected="false">{{ trans('file.Project') }} &amp; {{ trans('file.Task') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="employee_travel-tab" data-toggle="tab" href="#Profile_travel"
                                role="tab" aria-controls="Profile_travel"
                                aria-selected="false">{{ trans('file.Travel') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="employee_complain-tab" data-toggle="tab" href="#Employee_complain"
                                role="tab" aria-controls="Employee_complain"
                                aria-selected="false">{{ __('Complain') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="employee_payslip-tab" data-toggle="tab" href="#Employee_Payslip"
                                role="tab" aria-controls="Employee_Payslip"
                                aria-selected="false">{{ trans('file.Payslip') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="remainingLeaveType-tab" data-toggle="tab" href="#remainingLeaveType"
                                role="tab" aria-controls="remainingLeaveType"
                                aria-selected="false">{{ trans('file.Remaining Leave') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="activity-log-tab" data-toggle="tab" href="#ActivityLog" role="tab"
                                aria-controls="ActivityLog" aria-selected="false">{{ __('Activity Log') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="change-password-tab" data-toggle="tab" href="#ChangePassword"
                                role="tab" aria-controls="ChangePassword"
                                aria-selected="false">{{ __('Change Password') }}</a>
                        </li>
                    </ul>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="General" role="tabpanel"
                                    aria-labelledby="general-tab">
                                    <!--Contents for General / Basic starts here-->
                                    {{ __('Basic Information') }}
                                    <hr>
                                    <span id="form_result"></span>
                                    <form method="post" id="basic_sample_form" class="form-horizontal"
                                        enctype="multipart/form-data" autocomplete="off">

                                        @csrf
                                        @php
                                            $workRo = $profileWorkFieldsReadonly;
                                            $companyLabel =
                                                optional($companies->firstWhere('id', $employee->company_id))
                                                    ->company_name ?? '—';
                                            if ($employee->client_id && $employee->client) {
                                                $clientName = trim(
                                                    ($employee->client->first_name ?? '') .
                                                        ' ' .
                                                        ($employee->client->last_name ?? '')
                                                );
                                                $clientCompany = trim((string) ($employee->client->company_name ?? ''));
                                                $ownerLabel =
                                                    $clientName !== '' && $clientCompany !== ''
                                                        ? $clientName . ' — ' . $clientCompany
                                                        : ($clientName !== '' ? $clientName : $clientCompany);
                                                $ownerTypeLabel = trans('file.Client');
                                            } else {
                                                $ownerLabel = $companyLabel;
                                                $ownerTypeLabel = trans('file.Company');
                                            }
                                            $departmentLabel =
                                                optional($departments->firstWhere('id', $employee->department_id))
                                                    ->department_name ?? '—';
                                            $designationLabel =
                                                optional($designations->firstWhere('id', $employee->designation_id))
                                                    ->designation_name ?? '—';
                                            $roleLabel =
                                                optional($roles->firstWhere('id', $employee->role_users_id))->name ??
                                                '—';
                                            $statusLabel =
                                                optional($statuses->firstWhere('id', $employee->status_id))
                                                    ->status_title ?? '—';
                                            $shiftLabel = optional($employee->officeShift)->shift_name ?? '—';
                                            $locationLabel =
                                                optional($locations->firstWhere('id', $employee->location_id))
                                                    ->location_name ?? '—';
                                            $attendanceLabel =
                                                $employee->attendance_type === 'location_based'
                                                    ? __('Location Based')
                                                    : __('General');
                                        @endphp
                                        <div class="row">
                                            <div class="col-md-4 form-group">
                                                <label>{{ __('Image') }} *</label>

                                                <input type="hidden" name="employee_username"
                                                    value="{{ $employee->user->username }}">

                                                {{-- Preview + text --}}
                                                <div class="d-flex align-items-center mb-2">

                                                    @if (!empty($employee->user->profile_photo))
                                                        <img src="{{ url('uploads/profile_photos', $employee->user->profile_photo) }}"
                                                            height="100" width="100"
                                                            style="border-radius:50%;object-fit:cover;">
                                                    @else
                                                        @php
                                                            $name = trim(
                                                                ($employee->first_name ?? '') .
                                                                    ' ' .
                                                                    ($employee->last_name ?? ''),
                                                            );

                                                            if (empty($name)) {
                                                                $name = $employee->user->username ?? 'User';
                                                            }

                                                            $words = explode(' ', $name);

                                                            $initials = strtoupper(substr($words[0], 0, 1));

                                                            if (count($words) > 1) {
                                                                $initials .= strtoupper(substr($words[1], 0, 1));
                                                            }
                                                        @endphp

                                                        <div
                                                            style="
            width:100px;
            height:100px;
            border-radius:50%;
            background:#7C5CC4;
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:32px;
            font-weight:700;
        ">
                                                            {{ $initials }}
                                                        </div>
                                                    @endif

                                                    <div style="font-size:13px;color:#777;margin-left:10px;">
                                                        ({{ trans('file.gif,jpg,png,jpeg') }})
                                                    </div>

                                                </div>

                                                {{-- File Input --}}
                                                <input type="file" accept="image/*" id="profile_photo"
                                                    class="form-control @error('photo') is-invalid @enderror"
                                                    name="profile_photo">
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ __('First Name') }} <span class="text-danger">*</span></label>
                                                <input type="text" name="first_name" id="first_name"
                                                    placeholder="{{ __('First Name') }}" required class="form-control"
                                                    value="{{ $employee->first_name }}">
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ __('Last Name') }} <span class="text-danger">*</span></label>
                                                <input type="text" name="last_name" id="last_name"
                                                    placeholder="{{ __('Last Name') }}" required class="form-control"
                                                    value="{{ $employee->last_name }}">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>{{ __('Staff Id') }} @if (!$workRo)
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                @if ($workRo)
                                                    <input type="hidden" name="staff_id"
                                                        value="{{ $employee->staff_id }}">
                                                    <input type="text" readonly class="form-control bg-light"
                                                        value="{{ $employee->staff_id }}">
                                                @else
                                                    <input type="text" name="staff_id" id="staff_id"
                                                        placeholder="{{ __('Staff Id') }}" required class="form-control"
                                                        value="{{ $employee->staff_id }}">
                                                @endif
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.Username') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="username" id="username"
                                                    placeholder="{{ trans('file.Username') }}" required
                                                    class="form-control" value="{{ $employee->user->username }}">
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.Email') }}</label>
                                                <input type="text" name="email" id="email"
                                                    placeholder="{{ trans('file.Email') }}" class="form-control"
                                                    value="{{ $employee->email }}">
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.Phone') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="contact_no" id="contact_no"
                                                    placeholder="{{ trans('file.Phone') }}" required class="form-control"
                                                    value="{{ $employee->contact_no }}">
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ __('CNIC') }} <span class="text-danger">*</span></label>
                                                <input type="text" name="cnic" id="cnic"
                                                    class="form-control cnic-input" placeholder="35201-1234567-1"
                                                    maxlength="15" autocomplete="off" inputmode="numeric" required
                                                    value="{{ $employee->cnic }}">
                                                <small class="text-muted">{{ __('Format: 12345-1234567-1') }}</small>
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.Address') }} </label>
                                                <input type="text" name="address" id="address"
                                                    placeholder="Address" value="{{ $employee->address }}"
                                                    class="form-control">
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.City') }} </label>
                                                <input type="text" name="city" id="city"
                                                    placeholder="{{ trans('file.City') }}" value="{{ $employee->city }}"
                                                    class="form-control">
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.State/Province') }}
                                                </label>
                                                <input type="text" name="state" id="state"
                                                    placeholder="{{ trans('file.State/Province') }}"
                                                    value="{{ $employee->state }}" class="form-control">
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.ZIP') }} </label>
                                                <input type="text" name="zip_code" id="zip_code"
                                                    placeholder="{{ trans('file.ZIP') }}"
                                                    value="{{ $employee->zip_code }}" class="form-control">
                                            </div>


                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ trans('file.Country') }}</label>
                                                    <select name="country" id="country"
                                                        class="form-control selectpicker" data-live-search="true"
                                                        data-live-search-style="contains"
                                                        title="{{ __('Selecting', ['key' => trans('file.Country')]) }}...">
                                                        @foreach ($countries as $country)
                                                            <option value="{{ $country->id }}"
                                                                {{ $employee->country == $country->id ? 'selected' : '' }}>
                                                                {{ $country->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ __('Date Of Birth') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="date_of_birth" id="date_of_birth" required
                                                    autocomplete="off" class="form-control date"
                                                    value="{{ $employee->date_of_birth }}">
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.Gender') }}</label>
                                                <input type="hidden" name="gender_hidden"
                                                    value="{{ $employee->gender }}" />
                                                <select name="gender" id="gender" class="selectpicker form-control"
                                                    data-live-search="true" data-live-search-style="contains"
                                                    title="{{ __('Selecting', ['key' => trans('file.Gender')]) }}...">
                                                    <option value="Male">{{ trans('file.Male') }}</option>
                                                    <option value="Female">{{ trans('file.Female') }}</option>
                                                    <option value="Other">{{ trans('file.Other') }}</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ __('Marital Status') }}</label>
                                                <input type="hidden" name="marital_status_hidden"
                                                    value="{{ $employee->marital_status }}" />
                                                <select name="marital_status" id="marital_status"
                                                    class="selectpicker form-control" data-live-search="true"
                                                    data-live-search-style="contains"
                                                    title="{{ __('Selecting', ['key' => __('Marital Status')]) }}...">
                                                    <option value="single">{{ trans('file.Single') }}</option>
                                                    <option value="married">{{ trans('file.Married') }}</option>
                                                    <option value="widowed">{{ trans('file.Widowed') }}</option>
                                                    <option value="divorced">
                                                        {{ trans('file.Divorced/Separated') }}</option>
                                                </select>
                                            </div>

                                            @if ($workRo)
                                                <div class="col-md-12">
                                                    <hr>
                                                    <p class="text-muted mb-2"><i>{{ __('Work information') }}</i> —
                                                        {{ __('Managed by HR (read only)') }}</p>
                                                </div>
                                            @endif

                                            <div class="col-md-{{ $workRo ? '4' : '12' }}">
                                                @if ($workRo)
                                                    <div class="form-group">
                                                        <label>{{ __('Belongs To') }}</label>
                                                        <input type="hidden" name="company_id"
                                                            value="{{ $employee->company_id }}">
                                                        @if ($employee->client_id)
                                                            <input type="hidden" name="client_id"
                                                                value="{{ $employee->client_id }}">
                                                        @endif
                                                        <input type="text" readonly class="form-control bg-light"
                                                            value="{{ $ownerTypeLabel }}: {{ $ownerLabel }}">
                                                    </div>
                                                @else
                                                    @include('employee.partials.owner_fields', [
                                                        'employee' => $employee,
                                                        'companies' => $companies,
                                                        'clients' => $clients,
                                                    ])
                                                @endif
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ trans('file.Department') }} @if (!$workRo)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                    @if ($workRo)
                                                        <input type="hidden" name="department_id"
                                                            value="{{ $employee->department_id }}">
                                                        <input type="text" readonly class="form-control bg-light"
                                                            value="{{ $departmentLabel }}">
                                                    @else
                                                        <input type="hidden" name="department_id_hidden"
                                                            value="{{ $employee->department_id }}" />
                                                        <select name="department_id" id="department_id"
                                                            class="selectpicker form-control designation"
                                                            data-live-search="true" data-live-search-style="contains"
                                                            data-designation_name="designation_name"
                                                            title="{{ __('Selecting', ['key' => trans('file.Department')]) }}...">
                                                            @foreach ($departments as $department)
                                                                <option value="{{ $department->id }}">
                                                                    {{ $department->department_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.Designation') }} @if (!$workRo)
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                @if ($workRo)
                                                    <input type="hidden" name="designation_id"
                                                        value="{{ $employee->designation_id }}">
                                                    <input type="text" readonly class="form-control bg-light"
                                                        value="{{ $designationLabel }}">
                                                @else
                                                    <input type="hidden" name="designation_id_hidden"
                                                        value="{{ $employee->designation_id }}" />
                                                    <select name="designation_id" id="designation_id"
                                                        class="selectpicker form-control" data-live-search="true"
                                                        data-live-search-style="contains"
                                                        title="{{ __('Selecting', ['key' => trans('file.Designation')]) }}...">
                                                        @foreach ($designations as $designation)
                                                            <option value="{{ $designation->id }}">
                                                                {{ $designation->designation_name }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.Role') }} @if (!$workRo)
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                @if ($workRo)
                                                    <input type="text" readonly class="form-control bg-light"
                                                        value="{{ $roleLabel }}">
                                                @else
                                                    <input type="hidden" name="role_user_hidden"
                                                        value="{{ $employee->role_users_id }}" />
                                                    <select name="role_users_id" id="role_users_id" required
                                                        class="selectpicker form-control" data-live-search="true"
                                                        data-live-search-style="contains"
                                                        title="{{ __('Selecting', ['key' => trans('file.Role')]) }}...">
                                                        @foreach ($roles as $item)
                                                            <option value="{{ $item->id }}">{{ $item->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ trans('file.Status') }} @if (!$workRo)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                    @if ($workRo)
                                                        <input type="hidden" name="status_id"
                                                            value="{{ $employee->status_id }}">
                                                        <input type="text" readonly class="form-control bg-light"
                                                            value="{{ $statusLabel }}">
                                                    @else
                                                        <input type="hidden" name="status_id_hidden"
                                                            value="{{ $employee->status_id }}" />
                                                        <select name="status_id" id="status_id" required
                                                            class="form-control selectpicker" data-live-search="true"
                                                            data-live-search-style="contains"
                                                            title="{{ __('Selecting', ['key' => trans('file.Status')]) }}...">
                                                            @foreach ($statuses as $status)
                                                                <option value="{{ $status->id }}">
                                                                    {{ $status->status_title }}</option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.Office_Shift') }} @if (!$workRo)
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                @if ($workRo)
                                                    <input type="hidden" name="office_shift_id"
                                                        value="{{ $employee->office_shift_id }}">
                                                    <input type="text" readonly class="form-control bg-light"
                                                        value="{{ $shiftLabel }}">
                                                @else
                                                    <input type="hidden" name="office_shift_id_hidden"
                                                        value="{{ $employee->office_shift_id }}" />
                                                    <select name="office_shift_id" id="office_shift_id"
                                                        class="selectpicker form-control" data-live-search="true"
                                                        data-live-search-style="contains"
                                                        title="{{ __('Selecting', ['key' => trans('file.Office Shift')]) }}...">
                                                        @foreach ($office_shifts as $office_shift)
                                                            <option value="{{ $office_shift->id }}"
                                                                @selected((int) $employee->office_shift_id === (int) $office_shift->id)>
                                                                {{ $office_shift->shift_name }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ trans('file.Location') }}</label>
                                                @if ($workRo)
                                                    <input type="hidden" name="location_id"
                                                        value="{{ $employee->location_id }}">
                                                    <input type="text" readonly class="form-control bg-light"
                                                        value="{{ $locationLabel }}">
                                                @else
                                                    <input type="hidden" name="location_id_hidden"
                                                        value="{{ $employee->location_id }}" />
                                                    <select name="location_id" id="location_id"
                                                        class="selectpicker form-control" data-live-search="true"
                                                        data-live-search-style="contains"
                                                        title="{{ __('Selecting', ['key' => trans('file.Location')]) }}...">
                                                        @foreach ($locations as $location)
                                                            <option value="{{ $location->id }}">
                                                                {{ $location->location_name }}
                                                                @if ($location->max_radius)
                                                                    ({{ $location->max_radius }}m)
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ __('Date Of Joining') }} @if (!$workRo)
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                @if ($workRo)
                                                    <input type="hidden" name="joining_date"
                                                        value="{{ $employee->joining_date }}">
                                                    <input type="text" readonly class="form-control bg-light"
                                                        value="{{ $employee->joining_date }}">
                                                @else
                                                    <input type="text" name="joining_date" id="joining_date"
                                                        autocomplete="off" class="form-control date"
                                                        value="{{ $employee->joining_date }}">
                                                @endif
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label>{{ __('Date Of Leaving') }}</label>
                                                @if ($workRo)
                                                    <input type="hidden" name="exit_date"
                                                        value="{{ $employee->exit_date }}">
                                                    <input type="text" readonly class="form-control bg-light"
                                                        value="{{ $employee->exit_date ?: '—' }}">
                                                @else
                                                    <input type="text" name="exit_date" id="exit_date"
                                                        class="form-control date" value="{{ $employee->exit_date }}">
                                                @endif
                                            </div>

                                            <div class="col-md-4 form-group">
                                                <label class="text-bold">{{ __('Attendance Type') }} @if (!$workRo)
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                @if ($workRo)
                                                    <input type="hidden" name="attendance_type"
                                                        value="{{ $employee->attendance_type }}">
                                                    <input type="text" readonly class="form-control bg-light"
                                                        value="{{ $attendanceLabel }}">
                                                @else
                                                    <select name="attendance_type" id="attendance_type"
                                                        class="selectpicker form-control" data-live-search="true"
                                                        data-live-search-style="contains"
                                                        title="{{ __('Select Login Type...') }}">
                                                        <option value="general"
                                                            @if ($employee->attendance_type == 'general') selected @endif>
                                                            {{ __('General') }}</option>
                                                        <option value="location_based"
                                                            @if ($employee->attendance_type == 'location_based') selected @endif>
                                                            {{ __('Location Based') }}</option>
                                                    </select>
                                                @endif
                                            </div>

                                            {{-- <div class="col-md-4 form-group">
                                                        <label>{{__('Total Annual Leave')}}  (Year - {{date('Y')}})</label>
                                                        <input type="number" min="0" name="total_leave" id="total_leave" autocomplete="off" class="form-control" value="{{$employee->total_leave}}">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>{{__('Remaining Leave')}}  (Year - {{date('Y')}})</label>
                                                        <input type="number" readonly name="remaining_leave" id="remaining_leave" autocomplete="off" class="form-control" value="{{$employee->remaining_leave}}">
                                                        <small class="text-danger"><i>(Read Only)</i></small>
                                                    </div> --}}


                                            {{-- <div class="col-md-4"></div> --}}
                                            <div class="col-md-4"></div>

                                            <div class="mt-3 form-group row">
                                                <div class="form-group row mb-0">
                                                    <div class="col-md-6 offset-md-4">
                                                        <button type="submit" class="btn btn-primary">
                                                            {{ trans('file.Save') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="Leave" role="tabpanel" aria-labelledby="leave-tab">
                                    {{ __('Leave Info') }}
                                    <hr>
                                    @include('employee.leave.index')
                                </div>

                                <div class="tab-pane fade" id="WFH" role="tabpanel" aria-labelledby="wfh-tab">
                                    {{ __('WFH') }}
                                    <p class="text-muted small mb-0">
                                        {{ __('Work from home requests and approval status') }}</p>
                                    <hr>
                                    @include('employee.leave.wfh_index')
                                </div>

                                <div class="tab-pane fade" id="Employee_award" role="tabpanel"
                                    aria-labelledby="employee_award-tab">
                                    {{ __('Award Info') }}
                                    <hr>
                                    @include('employee.core_hr.award.index')
                                </div>

                                <div class="tab-pane fade" id="Employee_project_task" role="tabpanel"
                                    aria-labelledby="employee_project_task-tab">
                                    {{ trans('file.Project') }} &amp; {{ trans('file.Task') }}
                                    <hr>
                                    @include('employee.project_task.index')
                                </div>

                                <div class="tab-pane fade" id="Profile_travel" role="tabpanel"
                                    aria-labelledby="employee_travel-tab">
                                    {{ trans('file.Travel') }}
                                    <hr>
                                    @include('employee.core_hr.travel.index_profile')
                                </div>

                                <div class="tab-pane fade" id="Employee_complain" role="tabpanel"
                                    aria-labelledby="employee_complain-tab">
                                    {{ __('Complain') }}
                                    <hr>
                                    @include('employee.core_hr.ticket.index_profile')
                                </div>

                                <div class="tab-pane fade" id="Employee_Payslip" role="tabpanel"
                                    aria-labelledby="employee_payslip-tab">
                                    {{ trans('file.Payslip') }}
                                    <hr>
                                    @include('employee.payslip.index')
                                </div>

                                <div class="tab-pane fade" id="remainingLeaveType" role="tabpanel"
                                    aria-labelledby="remainingLeaveType-tab">
                                    {{ trans('file.Remaining Leave') }}
                                    <hr>
                                    @include('employee.remaining_leave.index')
                                </div>

                                <div class="tab-pane fade" id="ActivityLog" role="tabpanel"
                                    aria-labelledby="activity-log-tab">
                                    {{ __('Activity Log') }}
                                    <hr>
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <input class="form-control date" placeholder="{{ __('Select Date') }}"
                                                readonly id="activity_log_date" type="text">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-primary" id="activity_log_filter_btn">
                                                <i class="fa fa-search"></i> {{ trans('file.Search') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table id="profile-activity-log-table" class="table">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Action') }}</th>
                                                    <th>{{ __('Description') }}</th>
                                                    <th>{{ __('Performed By') }}</th>
                                                    <th>{{ __('IP Address') }}</th>
                                                    <th>{{ __('Date Time') }}</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="ChangePassword" role="tabpanel"
                                    aria-labelledby="change-password-tab">
                                    @include('profile.employee_related.change_password')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('employee.leave.info_modal')
        </div>
    </section>



@endsection

@push('scripts')
    <script type="text/javascript">
        function formatCnicValue(value) {
            const digits = (value || '').replace(/\D/g, '').slice(0, 13);
            if (digits.length <= 5) return digits;
            if (digits.length <= 12) return digits.slice(0, 5) + '-' + digits.slice(5);
            return digits.slice(0, 5) + '-' + digits.slice(5, 12) + '-' + digits.slice(12);
        }

        $(document).on('input', '.cnic-input', function() {
            this.value = formatCnicValue(this.value);
        });

        var profileWorkReadonly = @json($profileWorkFieldsReadonly);

        $('select[name="gender"]').val($('input[name="gender_hidden"]').val());
        $('#marital_status').selectpicker('val', $('input[name="marital_status_hidden"]').val());

        if (!profileWorkReadonly) {
            $('#role_users_id').selectpicker('val', $('input[name="role_user_hidden"]').val());
            @include('employee.partials.owner_fields_script')
            $('#status_id').selectpicker('val', $('input[name="status_id_hidden"]').val());
            $('#location_id').selectpicker('val', $('input[name="location_id_hidden"]').val());
        }


        $(document).ready(function() {

            let date = $('.date');
            date.datepicker({
                format: '{{ env('Date_Format_JS') }}',
                autoclose: true,
                todayHighlight: true
            });

            let month_year = $('.month_year');
            month_year.datepicker({
                format: "MM-yyyy",
                startView: "months",
                minViewMode: 1,
                autoclose: true,
            }).datepicker("setDate", new Date());

            @include('employee.leave.index_js_both_for_profile')
        });

        $('[data-table="immigration"]').one('click', function(e) {
            @include('employee.immigration.index_js')

        });

        $('[data-table="emergency"]').one('click', function(e) {
            @include('employee.emergency_contacts.index_js')
        });

        $('[data-table="document"]').one('click', function(e) {
            @include('employee.documents.index_js')
        });

        $('[data-table="qualification"]').one('click', function(e) {
            @include('employee.qualifications.index_js')
        });

        $('[data-table="work_experience"]').one('click', function(e) {
            @include('employee.work_experience.index_js')
        });

        $('[data-table="bank_account"]').one('click', function(e) {
            @include('employee.bank_account.index_js')
        });

        $('#profile-tab').one('click', function(e) {
            @include('employee.profile_picture.index_js')
        });

        $('#remainingLeaveType-tab').one('click', function(e) {
            @include('employee.remaining_leave.index_js')
        });

        $('#employee_project_task-tab').one('click', function(e) {
            @include('employee.project_task.project.index_js')

        });

        $('#employee_task-tab').one('click', function(e) {
            @include('employee.project_task.task.index_js')
        });

        let travelTabInitialized = false;
        let complainTabInitialized = false;
        let activityLogTabInitialized = false;

        const initTravelTab = function() {
            if (travelTabInitialized) {
                if (window.profileTravelTable) {
                    window.profileTravelTable.columns.adjust().draw(false);
                }
                return;
            }

            if (!$('#Profile_travel').hasClass('active')) {
                return;
            }

            travelTabInitialized = true;
            @include('employee.core_hr.travel.index_js_profile')
        };

        const initComplainTab = function() {
            if (complainTabInitialized) {
                return;
            }
            complainTabInitialized = true;
            @include('employee.core_hr.ticket.index_js_profile')
        };

        const initActivityLogTab = function() {
            if (activityLogTabInitialized) {
                return;
            }
            activityLogTabInitialized = true;

            const renderActivityLogTable = function(activity_date = '') {
                if ($.fn.DataTable.isDataTable('#profile-activity-log-table')) {
                    $('#profile-activity-log-table').DataTable().destroy();
                }

                $('#profile-activity-log-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('profile.activity_logs') }}",
                        data: {
                            activity_date: activity_date,
                            "_token": "{{ csrf_token() }}"
                        }
                    },
                    columns: [{
                            data: 'action',
                            name: 'action'
                        },
                        {
                            data: 'description',
                            name: 'description'
                        },
                        {
                            data: 'performed_by',
                            name: 'performed_by'
                        },
                        {
                            data: 'ip_address',
                            name: 'ip_address'
                        },
                        {
                            data: 'created_at',
                            name: 'created_at'
                        },
                    ],
                    order: [],
                    language: {
                        lengthMenu: '_MENU_ {{ __('records per page') }}',
                        info: '{{ trans('file.Showing') }} _START_ - _END_ (_TOTAL_)',
                        search: '{{ trans('file.Search') }}',
                        paginate: {
                            previous: '{{ trans('file.Previous') }}',
                            next: '{{ trans('file.Next') }}'
                        }
                    },
                });
            };

            renderActivityLogTable();

            $('#activity_log_filter_btn').off('click').on('click', function() {
                renderActivityLogTable($('#activity_log_date').val());
            });
        };

        $('#employee_travel-tab').on('shown.bs.tab', function() {
            initTravelTab();
        });

        $('#employee_complain-tab').on('shown.bs.tab', function() {
            initComplainTab();
        });

        $('#activity-log-tab').on('shown.bs.tab', function() {
            initActivityLogTab();
        });

        $('#employee_award-tab').one('click', function(e) {
            @include('employee.core_hr.award.index_js')
        });

        $('#employee_payslip-tab').one('click', function(e) {
            @include('employee.payslip.index_js')
        });

        $('#basic_sample_form').on('submit', function(event) {
            event.preventDefault();
            var attendance_type = $("#attendance_type").val();
            // console.log(attendance_type);

            $.ajax({
                url: "{{ route('profile.Update', $employee->id) }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function(data) {
                    console.log(data);
                    var html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (var count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        $('#remaining_leave').val(data.remaining_leave)
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                        location.reload();
                    }
                    $('#form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            });
        });

        $('.dynamic').change(function() {
            if ($(this).val() !== '') {
                let value = $(this).val();
                let dependent = $(this).data('shift_name');
                let _token = $('input[name="_token"]').val();
                $.ajax({
                    url: "{{ route('dynamic_office_shifts') }}",
                    method: "POST",
                    data: {
                        value: value,
                        _token: _token,
                        dependent: dependent
                    },
                    success: function(result) {
                        $('select').selectpicker("destroy");
                        $('#office_shift_id').html(result);
                        $('#designation_id').html('');
                        $('select').selectpicker();
                    }
                });
            }
        });

        $('.dynamic').change(function() {
            if ($(this).val() !== '') {
                let value = $(this).val();
                let dependent = $(this).data('dependent');
                let _token = $('input[name="_token"]').val();
                $.ajax({
                    url: "{{ route('dynamic_department') }}",
                    method: "POST",
                    data: {
                        value: value,
                        _token: _token,
                        dependent: dependent
                    },
                    success: function(result) {
                        $('select').selectpicker("destroy");
                        $('#department_id').html(result);
                        $('select').selectpicker();
                    }
                });
            }
        });

        $('.designation').change(function() {
            if ($(this).val() !== '') {
                let value = $(this).val();
                let designation_name = $(this).data('designation_name');
                let _token = $('input[name="_token"]').val();
                $.ajax({
                    url: "{{ route('dynamic_designation_department') }}",
                    method: "POST",
                    data: {
                        value: value,
                        _token: _token,
                        designation_name: designation_name
                    },
                    success: function(result) {
                        $('select').selectpicker("destroy");
                        $('#designation_id').html(result);
                        $('select').selectpicker();

                    }
                });
            }
        });

        // Login Type Change
        // $('#login_type').change(function() {
        //     var login_type = $('#login_type').val();
        //     if (login_type=='ip') {
        //         data = '<label class="text-bold">{{ __('IP Address') }} <span class="text-danger">*</span></label>';
        //         data += '<input type="text" name="ip_address" id="ip_address" placeholder="Type IP Address" required class="form-control">';
        //         $('#ipField').html(data)
        //     }else{
        //         $('#ipField').empty();
        //     }
        // });

        (function() {
            var h = window.location.hash;
            if (!h) {
                return;
            }
            var $top = $('#profileMainTabs a[href="' + h + '"]');
            if ($top.length) {
                $top.tab('show');
            }
        })();
    </script>
    <script>
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('profile_photo_preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
@endpush
