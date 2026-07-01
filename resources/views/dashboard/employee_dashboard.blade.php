@extends('layout.main')
@section('content')

    <section class="emp-dashboard">

        @include('shared.errors')

        @php
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if (empty($fullName)) {
                $fullName = $user->username ?? 'User';
            }
            $words = explode(' ', $fullName);
            $initials = strtoupper(substr($words[0], 0, 1));
            if (count($words) > 1) {
                $initials .= strtoupper(substr($words[1], 0, 1));
            }
        @endphp

        <div class="container-fluid">
            <div class="emp-hero card border-0 mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            @if (!empty($user->profile_photo) && file_exists(public_path('uploads/profile_photos/' . $user->profile_photo)))
                                <img src="{{ URL::to('/uploads/profile_photos') }}/{{ $user->profile_photo }}"
                                    class="emp-hero__avatar emp-hero__avatar--photo" alt="{{ $employee->full_name }}">
                            @else
                                <div class="emp-hero__avatar emp-hero__avatar--initials">{{ $initials }}</div>
                            @endif
                        </div>
                        <div class="col">
                            <span class="emp-hero__eyebrow">{{ __('Employee Portal') }}</span>
                            <h1 class="emp-hero__title mb-1">{{ $employee->full_name }}</h1>
                            <p class="emp-hero__subtitle mb-2">
                                {{ $employee->designation?->designation_name ?? '' }}
                                @if ($employee->department?->department_name)
                                    · {{ $employee->department->department_name }}
                                @endif
                                <span class="text-white-50">({{ $user->username }})</span>
                            </p>
                            <div class="emp-hero__meta">
                                <span><i class="dripicons-clock"></i> {{ __('Last Login') }}: {{ $user->last_login_date }}</span>
                                <span class="mx-2">|</span>
                                <span><i class="dripicons-calendar"></i>
                                    @if (!$shift_in)
                                        {{ __('No Shift Today') }}
                                    @else
                                        {{ $shift_in }} – {{ $shift_out }} ({{ $shift_name }})
                                    @endif
                                </span>
                            </div>
                            @if (!empty($shift_out) && ($today_overtime_total ?? '00:00') !== '00:00')
                                <p class="emp-hero__overtime mb-0 mt-2">
                                    <i class="dripicons-hourglass"></i> {{ __('Today Overtime') }}:
                                    <strong>{{ $today_overtime_total }}</strong>
                                </p>
                            @endif
                            @if (!empty($is_past_shift_while_clocked_in))
                                <span class="badge badge-warning mt-2">{{ __('Overtime in progress (regular shift)') }}</span>
                            @endif
                            @if (!empty($is_on_overtime_session))
                                <span class="badge badge-warning mt-2">{{ __('Overtime session active') }}</span>
                            @endif
                        </div>
                        <div class="col-lg-auto mt-3 mt-lg-0">
                            <div class="emp-hero__actions">
                                <a class="btn btn-light btn-sm emp-hero__btn" id="my_profile" href="{{ route('profile') }}">
                                    <i class="dripicons-user"></i> {{ trans('file.Profile') }}
                                </a>
                                @if (env('ENABLE_CLOCKIN_CLOCKOUT') != null)
                                    <form action="{{ route('employee_attendance.post', $employee->id) }}" method="POST"
                                        id="set_clocking" autocomplete="off" class="emp-hero__clock-form"
                                        data-attendance-type="{{ $employee->attendance_type ?? 'general' }}"
                                        data-office-lat="{{ $employee->location?->latitude ?? ($general_setting->latitude ?? '') }}"
                                        data-office-lng="{{ $employee->location?->longitude ?? ($general_setting->longitude ?? '') }}"
                                        data-max-radius="{{ $employee->location?->max_radius ?? ($general_setting->max_radius ?? '') }}">
                                        @csrf
                                        <input type="hidden" value="{{ $shift_in }}" name="office_shift_in" id="shift_in">
                                        <input type="hidden" value="{{ $shift_out }}" name="office_shift_out" id="shift_out">
                                        <input type="hidden" value="" name="in_out_value" id="in_out">
                                        <input type="hidden" name="latitude" id="user_lat">
                                        <input type="hidden" name="longitude" id="user_lng">
                                        <input type="hidden" name="location_accuracy" id="user_location_accuracy">
                                        <input type="hidden" name="location_captured_at" id="user_location_captured_at">
                                        @php
                                            $clockIpBlocked = ($employee->attendance_type ?? 'general') === 'ip_based' && $ipCheck != true;
                                        @endphp
                                        @if (!$employee_attendance || $employee_attendance->clock_in_out == 0)
                                            @if (!empty($can_overtime_clock_in))
                                                <button class="btn btn-warning btn-sm emp-hero__btn" @if ($clockIpBlocked) disabled @endif
                                                    type="button" id="overtime_clock_in_btn" onclick="handleAttendanceClockSubmit()">
                                                    <i class="dripicons-hourglass"></i> {{ __('Overtime Clock IN') }}
                                                </button>
                                            @elseif ($shift_in)
                                                <button class="btn btn-success btn-sm emp-hero__btn" @if ($clockIpBlocked) disabled @endif
                                                    type="button" id="clock_in_btn" onclick="handleAttendanceClockSubmit()">
                                                    <i class="dripicons-enter"></i> {{ __('Clock IN') }}
                                                </button>
                                            @endif
                                        @else
                                            @if (!empty($is_on_overtime_session))
                                                <button class="btn btn-warning btn-sm emp-hero__btn" @if ($clockIpBlocked) disabled @endif
                                                    type="button" id="overtime_clock_out_btn" onclick="handleAttendanceClockSubmit()">
                                                    <i class="dripicons-exit"></i> {{ __('Overtime Clock OUT') }}
                                                </button>
                                            @else
                                                <button class="btn btn-danger btn-sm emp-hero__btn" @if ($clockIpBlocked) disabled @endif
                                                    type="button" id="clock_out_btn" onclick="handleAttendanceClockSubmit()">
                                                    <i class="dripicons-exit"></i> {{ __('Clock OUT') }}
                                                </button>
                                            @endif
                                        @endif
                                    </form>
                                @endif
                            </div>
                            @if (env('ENABLE_CLOCKIN_CLOCKOUT') != null && $employee->attendance_type == 'ip_based' && $ipCheck != true)
                                <div class="emp-hero__clock-note mt-2 text-lg-right">
                                    <small class="text-white-50"><i>[Please login with your office's internet to clock in or clock out]</i></small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (!empty($is_location_head))
            <div class="container-fluid mb-4">
                <div class="emp-panel">
                    <div class="emp-panel__header">
                        <h5 class="emp-panel__title mb-0">{{ __('Center / Location Management') }}</h5>
                        <p class="emp-panel__subtitle mb-0">{{ __('Only employees assigned to your location(s) are shown in these sections.') }}</p>
                    </div>
                    <div class="emp-panel__body">
                        <div class="row">
                            @can('scoped-view-employees')
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('employees.index') }}" class="emp-quick-link">
                                    <i class="dripicons-user"></i>
                                    <span>{{ __('Users / Employees') }}</span>
                                    <span class="badge badge-primary">{{ $location_head_employee_count ?? 0 }}</span>
                                </a>
                            </div>
                            @endcan
                            @can('scoped-manage-leave')
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('leaves.index') }}" class="emp-quick-link">
                                    <i class="dripicons-archive"></i>
                                    <span>{{ __('L/ WFH Requests') }}</span>
                                </a>
                            </div>
                            @endcan
                            @can('view-my-locations')
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('locations.my') }}" class="emp-quick-link">
                                    <i class="dripicons-location"></i>
                                    <span>{{ __('My Centers / Locations') }}</span>
                                </a>
                            </div>
                            @endcan
                            @can('report-clock-in-locations')
                            @if (\App\Support\ManagedEmployeeScope::canAccessClockInLocationReport((int) auth()->id(), (int) auth()->user()->role_users_id))
                            <div class="col-md-4 mb-2">
                                <a href="{{ route('report.login-locations') }}" class="emp-quick-link">
                                    <i class="dripicons-map"></i>
                                    <span>{{ __('Clock-in Location Report') }}</span>
                                </a>
                            </div>
                            @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6 col-lg-3 mb-3">
                    <a href="{{ route('profile') . '#Employee_Payslip' }}" class="emp-kpi emp-kpi--cyan">
                        <div class="emp-kpi__icon dripicons-wallet" aria-hidden="true"></div>
                        <div>
                            <div class="emp-kpi__label">{{ __('Payslip') }}</div>
                            <div class="emp-kpi__meta">{{ __('View Details') }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <a href="{{ route('profile') . '#Employee_award' }}" class="emp-kpi emp-kpi--amber">
                        <div class="emp-kpi__icon dripicons-trophy" aria-hidden="true"></div>
                        <div>
                            <div class="emp-kpi__label">{{ $employee_award_count }} {{ __('Award') }}</div>
                            <div class="emp-kpi__meta">{{ __('View Details') }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <a href="{{ route('announcements.index') }}" class="emp-kpi emp-kpi--violet">
                        <div class="emp-kpi__icon dripicons-feed" aria-hidden="true"></div>
                        <div>
                            <div class="emp-kpi__label">{{ count($announcements) }} {{ trans('file.Announcement') }}</div>
                            <div class="emp-kpi__meta">{{ __('View Details') }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="emp-kpi emp-kpi--emerald" id="holiday" @if(count($holidays) === 0) style="cursor:default" @endif>
                        <div class="emp-kpi__icon dripicons-gaming" aria-hidden="true"></div>
                        <div>
                            <div class="emp-kpi__label">{{ count($holidays) }} {{ __('Upcoming Holidays') }}</div>
                            <div class="emp-kpi__meta">{{ __('View Details') }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="emp-kpi emp-kpi--violet">
                        <div class="emp-kpi__icon dripicons-calendar" aria-hidden="true"></div>
                        <div>
                            <div class="emp-kpi__label">{{ __('Leave') }}</div>
                            <div class="emp-kpi__actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('profile') . '#Leave' }}">{{ __('View Info') }}</a>
                                <button type="button" class="btn btn-sm btn-primary" id="leave_request">{{ __('Request') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="emp-kpi emp-kpi--cyan">
                        <div class="emp-kpi__icon dripicons-home" aria-hidden="true"></div>
                        <div>
                            <div class="emp-kpi__label">WFH</div>
                            <div class="emp-kpi__actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('profile') . '#WFH' }}">{{ __('View Info') }}</a>
                                <button type="button" class="btn btn-sm btn-primary" id="wfh_request">{{ __('Request') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="emp-kpi emp-kpi--amber">
                        <div class="emp-kpi__icon dripicons-direction" aria-hidden="true"></div>
                        <div>
                            <div class="emp-kpi__label">{{ __('Travel') }}</div>
                            <div class="emp-kpi__actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('profile') . '#Profile_travel' }}">{{ __('View Info') }}</a>
                                <button type="button" class="btn btn-sm btn-primary" id="travel_request">{{ __('Request') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="emp-kpi emp-kpi--rose">
                        <div class="emp-kpi__icon dripicons-ticket" aria-hidden="true"></div>
                        <div>
                            <div class="emp-kpi__label">{{ __('Complain') }}</div>
                            <div class="emp-kpi__actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('profile') . '#Employee_complain' }}">{{ __('View Info') }}</a>
                                <button type="button" class="btn btn-sm btn-primary" id="ticket_request">{{ __('Open') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="emp-panel h-100">
                        <div class="emp-panel__header d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="emp-panel__title mb-0">{{ __('Assigned Projects') }}</h4>
                                <p class="emp-panel__subtitle mb-0">{{ $assigned_projects_count }} {{ __('active') }}</p>
                            </div>
                            <span class="emp-panel__badge">{{ $assigned_projects_count }}</span>
                        </div>
                        <div class="emp-panel__body emp-list">
                            @forelse ($assigned_projects as $project)
                                @if (count($project->assignedProjects) != 0)
                                    <a href="{{ route('projects.show', $project->assignedProjects[0]->id) }}" class="emp-list__item">
                                        <span class="dripicons-checklist" aria-hidden="true"></span>
                                        <span>{{ $project->assignedProjects[0]->title }}</span>
                                    </a>
                                @endif
                            @empty
                                <p class="text-muted mb-0 small">{{ __('No projects assigned.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="emp-panel h-100">
                        <div class="emp-panel__header d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="emp-panel__title mb-0">{{ __('Assigned Tasks') }}</h4>
                                <p class="emp-panel__subtitle mb-0">{{ $assigned_tasks_count }} {{ __('pending') }}</p>
                            </div>
                            <span class="emp-panel__badge">{{ $assigned_tasks_count }}</span>
                        </div>
                        <div class="emp-panel__body emp-list">
                            @forelse ($assigned_tasks as $task)
                                @if (count($task->assignedTasks) != 0)
                                    <a href="{{ route('tasks.show', $task->assignedTasks[0]->id) }}" class="emp-list__item">
                                        <span class="dripicons-to-do" aria-hidden="true"></span>
                                        <span>{{ $task->assignedTasks[0]->task_name }}</span>
                                    </a>
                                @endif
                            @empty
                                <p class="text-muted mb-0 small">{{ __('No tasks assigned.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="emp-panel h-100">
                        <div class="emp-panel__header d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="emp-panel__title mb-0">{{ __('Assigned Tickets') }}</h4>
                                <p class="emp-panel__subtitle mb-0">{{ $assigned_tickets_count }} {{ __('open') }}</p>
                            </div>
                            <span class="emp-panel__badge">{{ $assigned_tickets_count }}</span>
                        </div>
                        <div class="emp-panel__body emp-list">
                            @forelse ($assigned_tickets as $ticket)
                                @if (count($ticket->assignedTickets) != 0)
                                    <a href="{{ route('tickets.show', $ticket->assignedTickets[0]->ticket_code) }}" class="emp-list__item">
                                        <span class="dripicons-ticket" aria-hidden="true"></span>
                                        <span>{{ $ticket->assignedTickets[0]->subject }}</span>
                                    </a>
                                @endif
                            @empty
                                <p class="text-muted mb-0 small">{{ __('No tickets assigned.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="holidayModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('Holidays') }}</h5>
                        <button type="button" data-dismiss="modal" id="close" aria-label="Close"
                            class="close"><span aria-hidden="true">×</span></button>
                    </div>

                    <div class="modal-body">
                        @foreach ($holidays as $holiday)
                            <div><strong
                                    class="name blue-text">{{ $holiday->event_name }}</strong>{{ trans('file.From') }}
                                :{{ $holiday->start_date }} {{ trans('file.To') }}:{{ $holiday->end_date }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div id="leaveModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 id="leaveModalTitle" class="modal-title">{{ __('Leave Request') }}</h5>
                        <button type="button" data-dismiss="modal" id="close" aria-label="Close"
                            class="close"><span aria-hidden="true">×</span></button>
                    </div>

                    <div class="modal-body">
                        <span id="leave_form_result"></span>
                        <form method="post" id="leaveSampleForm" class="form-horizontal">

                            @csrf
                            <div class="row">

                                <div class="col-md-6 form-group">
                                    <label id="request_type_label">{{ __('Leave Type') }} *</label>
                                    <select name="leave_type" id="leave_type" class="form-control selectpicker "
                                        data-live-search="true" data-live-search-style="contains"
                                        title='{{ __('Leave Type') }}'>
                                        @foreach ($leave_types as $leave_type)
                                            <option value="{{ $leave_type->id }}"
                                                data-day="{{ $leave_type->allocated_day }}"
                                                data-is-wfh="{{ str_contains(strtolower($leave_type->leave_type), 'wfh') || str_contains(strtolower($leave_type->leave_type), 'work from home') ? 1 : 0 }}">
                                                {{ $leave_type->leave_type }}</option>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="col-md-4 form-group">
                                    <label>{{ __('Start Date') }} *</label>
                                    <input type="text" name="start_date" id="start_date" class="form-control date"
                                        value="">
                                </div>

                                <div class="col-md-4 form-group">
                                    <label>{{ __('End Date') }} *</label>
                                    <input type="text" name="end_date" id="end_date" class="form-control test date"
                                        value="">
                                </div>

                                <div class="col-md-4 form-group">
                                    <label>{{ __('Total Days') }}</label>
                                    <input type="text" readonly id="total_days" class="form-control">
                                </div>

                                <div class="col-md-6 form-group">
                                    <label for="leave_reason">{{ trans('file.Description') }}</label>
                                    <textarea class="form-control" id="leave_reason" name="leave_reason" rows="3"></textarea>
                                </div>

                                <div class="container">
                                    <div class="form-group" align="center">
                                        <input type="hidden" name="company_id" value="{{ $employee->company_id }}" />
                                        <input type="hidden" name="department_id"
                                            value="{{ $employee->department_id }}" />
                                        <input type="hidden" name="employee_id" value="{{ $employee->id }}" />
                                        <input type="hidden" name="status" value="pending" />

                                        <input type="hidden" name="diff_date_hidden" id="diff_date_hidden" />
                                        <input type="submit" name="action_button" class="btn btn-warning"
                                            value={{ trans('file.Add') }} />
                                    </div>
                                </div>

                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

        <div id="travelModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('Travel Request') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true">×</span></button>
                    </div>

                    <div class="modal-body">
                        <span id="travel_form_result"></span>
                        <form method="post" id="travel_sample_form" class="form-horizontal">

                            @csrf
                            <div class="row">


                                <div class="col-md-6 form-group">
                                    <label>{{ __('Arrangement Type') }}</label>
                                    <select name="travel_type_id" class="form-control selectpicker "
                                        data-live-search="true" data-live-search-style="contains"
                                        title='{{ __('Selecting', ['key' => trans('file.Arrangement')]) }}...'>
                                        @foreach ($travel_types as $travel_type)
                                            <option value="{{ $travel_type->id }}">{{ $travel_type->arrangement_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="col-md-6 form-group">
                                    <label>{{ __('Purpose Of Visit') }} *</label>
                                    <input type="text" name="purpose_of_visit" class="form-control"
                                        placeholder="{{ __('Purpose Of Visit') }}">
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>{{ __('Place Of Visit') }} *</label>
                                    <input type="text" name="place_of_visit" class="form-control"
                                        placeholder="{{ __('Place Of Visit') }}">
                                </div>


                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ trans('file.Description') }}</label>
                                        <textarea class="form-control" name="description" rows="3"></textarea>
                                    </div>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>{{ __('Start Date') }} *</label>
                                    <input type="text" name="start_date" class="form-control date" autocomplete="off"
                                        value="">
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>{{ __('End Date') }} *</label>
                                    <input type="text" name="end_date" class="form-control date" autocomplete="off"
                                        value="">
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>{{ __('Expected Budget') }}</label>
                                    <input type="text" name="expected_budget" class="form-control">
                                </div>


                                <div class="col-md-6 form-group">
                                    <label>{{ __('Travel Mode') }}</label>
                                    <select name="travel_mode" class="form-control selectpicker " data-live-search="true"
                                        data-live-search-style="contains" title='{{ __('Travel Mode') }}'>
                                        <option value="By Bus">{{ __('By Bus') }}</option>
                                        >
                                        <option value="By Train">{{ __('By Train') }}</option>
                                        <option value="By Plane">{{ __('By Plane') }}</option>
                                        <option value="By Taxi">{{ __('By Taxi') }}</option>
                                        <option value="By Rental Car">{{ __('By Rental Car') }}</option>
                                        <option value="By Other">{{ __('By Other') }}</option>
                                    </select>
                                </div>


                                <div class="container">
                                    <div class="form-group" align="center">

                                        <input type="hidden" name="company_id" value="{{ $employee->company_id }}" />
                                        <input type="hidden" name="department_id"
                                            value="{{ $employee->department_id }}" />
                                        <input type="hidden" name="employee_id" value="{{ $employee->id }}" />
                                        <input type="hidden" name="status" value="pending" />

                                        <input type="submit" name="action_button" class="btn btn-warning"
                                            value={{ trans('file.Add') }} />
                                    </div>
                                </div>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>

        <div id="ticketModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('Open Complain') }}</h5>
                        <button type="button" data-dismiss="modal" id="close" aria-label="Close"
                            class="close"><span aria-hidden="true">×</span></button>
                    </div>

                    <div class="modal-body">
                        <span id="ticket_form_result"></span>
                        <form method="post" id="ticket_sample_form" class="form-horizontal"
                            enctype="multipart/form-data">

                            @csrf

                            <div class="row">


                                <div class="col-md-6 form-group">
                                    <label>{{ trans('file.Priority') }}</label>
                                    <select name="ticket_priority" id="ticket_priority"
                                        class="form-control selectpicker " data-live-search="true"
                                        data-live-search-style="contains"
                                        title='{{ __('Selecting', ['key' => trans('file.Priority')]) }}...'>
                                        <option value="low">{{ trans('file.Low') }}</option>
                                        <option value="medium">{{ trans('file.Medium') }}</option>
                                        <option value="high">{{ trans('file.High') }}</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>{{ trans('file.Subject') }} *</label>
                                    <input type="text" name="subject" id="subject" class="form-control"
                                        placeholder="{{ trans('file.Subject') }}">
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>{{ __('Complain Note') }}</label>
                                    <input type="text" name="ticket_note" id="ticket_note" class="form-control"
                                        placeholder="{{ trans('file.Optional') }}">
                                </div>

                                <div class="col-md-6 form-group hide_edit">
                                    <label>{{ __('Complain Attachments') }} </label>
                                    <input type="file" name="ticket_attachments" id="ticket_attachments"
                                        class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ trans('file.Description') }}</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>
                                </div>


                                <div class="container">
                                    <div class="form-group" align="center">
                                        <input type="hidden" name="company_id" value="{{ $employee->company_id }}" />
                                        <input type="hidden" name="department_id"
                                            value="{{ $employee->department_id }}" />
                                        <input type="hidden" name="employee_id" value="{{ $employee->id }}" />
                                        {{-- <input type="hidden" name="ticket_status" value="pending"/> --}}
                                        <input type="hidden" name="ticket_status" value="open" />

                                        <input type="submit" name="action_button" class="btn btn-warning"
                                            value={{ trans('file.Add') }} />

                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection

@push('css')
<style>
    .emp-dashboard {
        --emp-primary: #37205b;
        --emp-violet: #5b4a9a;
        --emp-violet-light: #7c5cc4;
        --emp-cyan: #19aed9;
        --emp-ink: #1e293b;
        --emp-muted: #64748b;
        --emp-border: #e2e8f0;
    }

    .emp-dashboard .emp-hero {
        background: linear-gradient(135deg, #37205b 0%, #5b4a9a 55%, #7c5cc4 100%);
        color: #fff;
        border-radius: 16px;
        box-shadow: 0 18px 40px rgba(55, 32, 91, 0.18);
    }

    .emp-dashboard .emp-hero__avatar {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, 0.35);
        flex-shrink: 0;
    }

    .emp-dashboard .emp-hero__avatar--photo { object-fit: cover; }
    .emp-dashboard .emp-hero__avatar--initials {
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.15);
        font-size: 2rem;
        font-weight: 700;
    }

    .emp-dashboard .emp-hero__eyebrow {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.85;
        margin-bottom: 4px;
    }

    .emp-dashboard .emp-hero__title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0; }
    .emp-dashboard .emp-hero__subtitle { opacity: 0.92; font-size: 0.92rem; }
    .emp-dashboard .emp-hero__meta { font-size: 0.85rem; opacity: 0.88; }
    .emp-dashboard .emp-hero__overtime { font-size: 0.88rem; opacity: 0.95; }

    .emp-dashboard .emp-hero__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .emp-dashboard .emp-hero__clock-form {
        display: inline-flex;
        align-items: center;
        margin: 0;
    }

    .emp-dashboard .emp-hero__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 34px;
        line-height: 1.2;
        margin: 0;
        white-space: nowrap;
        vertical-align: middle;
    }

    .emp-dashboard .emp-hero__btn i:before {
        line-height: 1;
        vertical-align: middle;
    }

    .emp-dashboard .emp-kpi {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #fff;
        border: 1px solid var(--emp-border);
        border-radius: 14px;
        padding: 18px;
        height: 100%;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .emp-dashboard .emp-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        text-decoration: none;
        color: inherit;
    }

    .emp-dashboard .emp-kpi__icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
        line-height: 1;
    }

    .emp-dashboard [class*="dripicons-"]:before {
        font-family: "dripicons-v2" !important;
        font-style: normal !important;
        line-height: 1;
    }

    .emp-dashboard .emp-kpi--violet .emp-kpi__icon { background: rgba(91, 74, 154, 0.12); color: var(--emp-violet); }
    .emp-dashboard .emp-kpi--cyan .emp-kpi__icon { background: rgba(25, 174, 217, 0.12); color: var(--emp-cyan); }
    .emp-dashboard .emp-kpi--amber .emp-kpi__icon { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
    .emp-dashboard .emp-kpi--emerald .emp-kpi__icon { background: rgba(16, 185, 129, 0.12); color: #10b981; }
    .emp-dashboard .emp-kpi--rose .emp-kpi__icon { background: rgba(244, 96, 91, 0.12); color: #f4605b; }

    .emp-dashboard .emp-kpi__label { font-weight: 700; color: var(--emp-ink); font-size: 0.95rem; }
    .emp-dashboard .emp-kpi__meta { font-size: 0.8rem; color: var(--emp-muted); }

    .emp-dashboard .emp-kpi__actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px;
    }

    .emp-dashboard .emp-kpi__actions .btn {
        font-size: 0.72rem;
        padding: 3px 10px;
        line-height: 1.35;
        border-radius: 6px;
        white-space: nowrap;
    }

    .emp-dashboard .emp-panel {
        background: #fff;
        border: 1px solid var(--emp-border);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .emp-dashboard .emp-panel__header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--emp-border);
        background: linear-gradient(180deg, #fafbfe 0%, #fff 100%);
    }

    .emp-dashboard .emp-panel__title { font-size: 1rem; font-weight: 700; color: var(--emp-ink); }
    .emp-dashboard .emp-panel__subtitle { font-size: 0.82rem; color: var(--emp-muted); }
    .emp-dashboard .emp-panel__badge {
        background: rgba(91, 74, 154, 0.12);
        color: var(--emp-violet);
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 0.85rem;
    }

    .emp-dashboard .emp-panel__body { padding: 12px 16px 16px; }

    .emp-dashboard .emp-quick-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border: 1px solid var(--emp-border);
        border-radius: 10px;
        text-decoration: none;
        color: var(--emp-ink);
        transition: background 0.15s ease;
    }

    .emp-dashboard .emp-quick-link:hover {
        background: rgba(91, 74, 154, 0.06);
        text-decoration: none;
        color: var(--emp-ink);
    }

    .emp-dashboard .emp-quick-link .badge { margin-left: auto; }

    .emp-dashboard .emp-list__item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 8px;
        color: var(--emp-ink);
        text-decoration: none;
        border-bottom: 1px dashed var(--emp-border);
        font-size: 0.9rem;
        transition: background 0.15s ease;
    }

    .emp-dashboard .emp-list__item:last-child { border-bottom: none; }
    .emp-dashboard .emp-list__item:hover {
        background: rgba(91, 74, 154, 0.06);
        text-decoration: none;
        color: var(--emp-violet);
    }

    .emp-dashboard .emp-list__item > [class*="dripicons-"] {
        font-size: 1rem;
        color: var(--emp-violet);
        flex-shrink: 0;
    }
</style>
@endpush

@push('scripts')
    <script>
        (function($) {
            "use strict";


            let startDateInput = $('#start_date');
            let endDateInput = $('#end_date');
            let totalDaysInput = $('#total_days');

            $(document).ready(function() {
                let date = $('.date');
                date.datepicker({
                    format: '{{ env('Date_Format_JS') }}',
                    autoclose: true,
                    todayHighlight: true,
                    startDate: new Date(),
                });

                // const startDateInput = $('#start_date');
                // const endDateInput = $('#end_date');
                // const totalDaysInput = $('#total_days');

                startDateInput.on('change', function() {
                    getDateResult();
                });

                endDateInput.on('change', function() {
                    getDateResult();
                });

                const getDateResult = () => {

                    // Convert Date formate to YYYY-MM-DD
                    if (!startDateInput.val() || !endDateInput.val()) {
                        return;
                    }

                    let startDateFormat = convertDataFormat(startDateInput.val());
                    let endDateFormat = convertDataFormat(endDateInput.val());

                    let startDate = new Date(startDateFormat);
                    let endDate = new Date(endDateFormat);
                    let timeDiff = endDate.getTime() - startDate.getTime();
                    // Convert the difference from milliseconds to days and update the totalDays input field
                    let totalDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                    if (totalDays < 0) {
                        totalDaysInput.val(0);
                    } else {
                        totalDaysInput.val(totalDays);
                    }
                }

                const convertDataFormat = getDateValue => {
                    const inputDate = getDateValue;
                    const parts = inputDate.split("-");
                    const date = new Date(parts[2], parts[1] - 1, parts[0]);
                    const outputDate = date.toISOString().substring(0, 10);
                    return outputDate;
                }
            });

            // let date = $('.date');
            // date.datepicker({
            //     format: '{{ env('Date_Format_JS') }}',
            //     autoclose: true,
            //     todayHighlight: true
            // });

            $('#holiday').on('click', function() {
                $('#holidayModal').modal('show');
            });

            const wfhFallbackTypeId = @json(optional(collect($leave_types)->first(function ($item) {
                        $name = strtolower((string) ($item->leave_type ?? ''));
                        return str_contains($name, 'wfh') || str_contains($name, 'work from home');
                    }))->id);
            let leaveRequestMode = 'leave';
            let lockedWfhTypeId = null;
            const allLeaveTypeOptions = $('#leave_type option').clone();

            const filterRequestTypes = function() {
                let firstAvailableValue = '';
                $('#leave_type').empty();
                let hasWfhOption = false;

                allLeaveTypeOptions.each(function() {
                    let option = $(this).clone();
                    let isWfhAttr = String(option.attr('data-is-wfh') || '').trim();
                    let optionText = String(option.text() || '').toLowerCase();
                    let isWfh = isWfhAttr === '1' || optionText.includes('wfh') || optionText.includes(
                        'work from home');
                    if (isWfh) {
                        hasWfhOption = true;
                    }
                    let shouldShow = leaveRequestMode === 'wfh' ? isWfh : !isWfh;
                    if (shouldShow) {
                        $('#leave_type').append(option);
                        if (firstAvailableValue === '') {
                            firstAvailableValue = option.val();
                        }
                    }
                });

                if (leaveRequestMode === 'wfh' && !hasWfhOption) {
                    const fallbackOptionValue = wfhFallbackTypeId ? String(wfhFallbackTypeId) : '0';
                    const fallbackWfhOption = $('<option value="' + fallbackOptionValue +
                        '" data-day="365" data-is-wfh="1">WFH</option>');
                    $('#leave_type').append(fallbackWfhOption);
                    firstAvailableValue = fallbackOptionValue;
                }

                // Reinitialize picker after dynamic option rebuild
                $('#leave_type').selectpicker('destroy');
                $('#leave_type').selectpicker();

                if (firstAvailableValue) {
                    $('#leave_type').selectpicker('val', firstAvailableValue);
                    lockedWfhTypeId = leaveRequestMode === 'wfh' ? String(firstAvailableValue) : null;
                } else {
                    $('#leave_type').selectpicker('val', '');
                    lockedWfhTypeId = null;
                    let html = leaveRequestMode === 'wfh' ?
                        '<div class="alert alert-danger"><p>{{ __('WFH leave type is not configured yet. Please contact HR/Admin.') }}</p></div>' :
                        '<div class="alert alert-danger"><p>{{ __('Leave types are not configured yet. Please contact HR/Admin.') }}</p></div>';
                    $('#leave_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
                // Keep select enabled so value is submitted; lock only UI interaction in WFH mode.
                $('#leave_type').prop('disabled', false);
                $('#leave_type').selectpicker('refresh');

                const pickerBtn = $('#leave_type').siblings('.bootstrap-select').find('button.dropdown-toggle');
                if (leaveRequestMode === 'wfh') {
                    pickerBtn.prop('disabled', true);
                    pickerBtn.css('pointer-events', 'none');
                } else {
                    pickerBtn.prop('disabled', false);
                    pickerBtn.css('pointer-events', '');
                }
            };

            $('#leave_type').on('changed.bs.select', function() {
                if (leaveRequestMode === 'wfh' && lockedWfhTypeId) {
                    $('#leave_type').selectpicker('val', lockedWfhTypeId);
                    $('#leave_type').selectpicker('refresh');
                }
            });

            $('#leave_request').on('click', function() {
                leaveRequestMode = 'leave';
                $('#leaveModalTitle').text("{{ __('Leave Request') }}");
                $('#request_type_label').text("{{ __('Leave Type') }} *");
                $('#leaveModal').modal('show');
                filterRequestTypes();
            });

            $('#wfh_request').on('click', function() {
                leaveRequestMode = 'wfh';
                $('#leaveModalTitle').text("{{ __('WFH Request') }}");
                $('#request_type_label').text("{{ __('WFH Type') }} *");
                $('#leaveModal').modal('show');
                filterRequestTypes();
            });

            $('#travel_request').on('click', function() {
                $('#travelModal').modal('show');
            });

            $('#ticket_request').on('click', function() {
                $('#ticketModal').modal('show');
            });


            $('#leaveSampleForm').on('submit', function(event) {
                event.preventDefault();

                let start_date = $("#start_date").datepicker('getDate');
                let end_date = $("#end_date").datepicker('getDate');
                let dayDiff = Math.ceil((end_date - start_date) / (1000 * 60 * 60 * 24)) + 1;
                $('#diff_date_hidden').val(dayDiff);

                let allocatedDay = $("#leave_type option:selected").data('day');
                let selectedLeaveText = ($("#leave_type option:selected").text() || '').toLowerCase();
                let isWfhType = selectedLeaveText.includes('wfh') || selectedLeaveText.includes(
                    'work from home');

                let html = '';
                if (!isWfhType && allocatedDay < totalDaysInput.val()) {
                    html += '<div class="alert alert-danger">' + '<p>Insufficient Allocated Day</p>' + '</div>';
                    return $('#leave_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }

                $.ajax({
                    url: "{{ route('leaves.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function(data) {
                        console.log(data);

                        if (data.errors) {
                            html += '<div class="alert alert-danger">';
                            for (let count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        } else if (data.limit) {
                            html += '<div class="alert alert-danger">' + data.limit + '</div>';
                        } else if (data.remaining_leave) {
                            html += '<div class="alert alert-danger">' + data.remaining_leave +
                                '</div>';
                        } else if (data.error) {
                            html += '<div class="alert alert-danger">' + data.error + '</div>';
                        } else if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#leave_form_result').html(html).slideDown(300);

                            setTimeout(function() {
                                $('#leaveSampleForm')[0].reset();
                                $('select').selectpicker('refresh');
                                $('.date').datepicker('update');
                                $('#leaveModal').modal('hide');
                                $('#leave_form_result').html('');
                            }, 1500);
                            return;
                        }
                        $('#leave_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                });



            });

            $('#travel_sample_form').on('submit', function(event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('travels.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function(data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (var count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.error) {
                            html = '<div class="alert alert-danger">' + data.error + '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#travel_form_result').html(html).slideDown(300);

                            setTimeout(function() {
                                $('#travel_sample_form')[0].reset();
                                $('select').selectpicker('refresh');
                                $('.date').datepicker('update');
                                $('#travelModal').modal('hide');
                                $('#travel_form_result').html('');
                            }, 1500);
                            return;
                        }
                        $('#travel_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
            });


            $('#ticket_sample_form').on('submit', function(event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('tickets.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function(data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (var count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#ticket_form_result').html(html).slideDown(300);

                            setTimeout(function() {
                                $('#ticket_sample_form')[0].reset();
                                $('select').selectpicker('refresh');
                                $('#ticketModal').modal('hide');
                                $('#ticket_form_result').html('');
                            }, 1500);
                            return;
                        }
                        $('#ticket_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
            });

        })(jQuery);
    </script>

    @include('partials.attendance_clock_gps', ['clockFormId' => 'set_clocking'])
@endpush
