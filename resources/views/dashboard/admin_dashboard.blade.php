@extends('layout.main')
@section('content')

    @php
        $employeeTotal = $employees->count();
        $absentToday = max($employeeTotal - $attendance_count, 0);
        $attendanceRate = $employeeTotal > 0 ? round(($attendance_count / $employeeTotal) * 100) : 0;

        $kpiCards = [
            [
                'label' => trans('file.Employees'),
                'value' => $employeeTotal,
                'sub' => __('Active workforce'),
                'icon' => 'dripicons-user-group',
                'tone' => 'violet',
                'route' => route('employees.index'),
            ],
            [
                'label' => trans('file.Attendance'),
                'value' => $attendance_count . ' / ' . $employeeTotal,
                'sub' => $attendanceRate . '% ' . __('present today'),
                'icon' => 'dripicons-checkmark',
                'tone' => 'amber',
                'route' => route('attendances.index'),
            ],
            [
                'label' => __('Total Leave'),
                'value' => $leave_count,
                'sub' => __('On leave today'),
                'icon' => 'dripicons-calendar',
                'tone' => 'emerald',
                'route' => route('leaves.index'),
            ],
            [
                'label' => trans('file.Announcement'),
                'value' => count($announcements),
                'sub' => __('Active notices'),
                'icon' => 'dripicons-broadcast',
                'tone' => 'cyan',
                'route' => route('announcements.index'),
            ],
            [
                'label' => __('Open Complain'),
                'value' => $ticket_count,
                'sub' => __('Needs attention'),
                'icon' => 'dripicons-warning',
                'tone' => 'rose',
                'route' => route('tickets.index'),
            ],
            [
                'label' => __('Completed Projects'),
                'value' => $completed_projects,
                'sub' => __('Finished portfolio'),
                'icon' => 'dripicons-checklist',
                'tone' => 'indigo',
                'route' => route('projects.index'),
            ],
        ];
    @endphp

    <section class="hr-dashboard">
        <div class="container-fluid">
            <div class="hr-hero card border-0 mb-4">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <span class="hr-hero__eyebrow">{{ __('HR Operations') }}</span>
                        <h1 class="hr-hero__title mb-1">{{ trans('file.Welcome') }}, {{ auth()->user()->username }}</h1>
                        <p class="hr-hero__subtitle mb-0">{{ __('Attendance, leave, projects, and team calendar overview') }}</p>
                    </div>
                    <div class="hr-hero__date text-right">
                        <div class="hr-hero__day">{{ now()->englishDayOfWeek }}</div>
                        <div class="hr-hero__full-date">{{ now()->format(env('Date_Format')) }}</div>
                    </div>
                </div>
            </div>

            <div class="row">
                @foreach ($kpiCards as $card)
                    <div class="col-sm-6 col-md-4 col-xl mb-3">
                        <a href="{{ $card['route'] }}" class="hr-kpi hr-kpi--{{ $card['tone'] }}">
                            <div class="hr-kpi__icon"><i class="{{ $card['icon'] }}"></i></div>
                            <div class="hr-kpi__content">
                                <span class="hr-kpi__label">{{ $card['label'] }}</span>
                                <div class="hr-kpi__value">{{ $card['value'] }}</div>
                                <span class="hr-kpi__meta">{{ $card['sub'] }}</span>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="row mt-1">
                <div class="col-lg-4 mb-4">
                    <div class="hr-panel h-100">
                        <div class="hr-panel__header">
                            <h4 class="hr-panel__title mb-0">{{ __('Today Attendance') }}</h4>
                            <p class="hr-panel__subtitle mb-0">{{ __('Present vs absent employees') }}</p>
                        </div>
                        <div class="hr-panel__body">
                            <div class="hr-chart-wrap">
                                <canvas id="attendance_chart"
                                    data-present_count="{{ $attendance_count }}"
                                    data-absent_count="{{ $absentToday }}"
                                    data-present_level="{{ __('Present') }}"
                                    data-absent_level="{{ __('Absent') }}"></canvas>
                            </div>
                            <div class="hr-attendance-stats mt-3">
                                <div class="hr-attendance-stat">
                                    <span class="hr-attendance-stat__dot hr-attendance-stat__dot--present"></span>
                                    <span>{{ __('Present') }}: <strong>{{ $attendance_count }}</strong></span>
                                </div>
                                <div class="hr-attendance-stat">
                                    <span class="hr-attendance-stat__dot hr-attendance-stat__dot--absent"></span>
                                    <span>{{ __('Absent') }}: <strong>{{ $absentToday }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="hr-panel h-100">
                        <div class="hr-panel__header">
                            <h4 class="hr-panel__title mb-0">{{ __('Employee Designation') }}</h4>
                            <p class="hr-panel__subtitle mb-0">{{ __('Headcount by role') }}</p>
                        </div>
                        <div class="hr-panel__body">
                            <div class="hr-chart-wrap">
                                <canvas id="designation_chart"
                                    data-desig_bgcolor='@json($desig_bgcolor_array)'
                                    data-hover_desig_bgcolor='@json($desig_hover_bgcolor_array)'
                                    data-desig_emp_count='@json($desig_count_array)'
                                    data-desig_label='@json($desig_name_array)'></canvas>
                            </div>
                            <div class="hr-chart-legend-scroll" id="designation_chart_legend" aria-label="{{ __('Designation legend') }}"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="hr-panel h-100">
                        <div class="hr-panel__header">
                            <h4 class="hr-panel__title mb-0">{{ __('Employee Department') }}</h4>
                            <p class="hr-panel__subtitle mb-0">{{ __('Team distribution') }}</p>
                        </div>
                        <div class="hr-panel__body">
                            <div class="hr-chart-wrap">
                                <canvas id="department_chart"
                                    data-dept_bgcolor='@json($dept_bgcolor_array)'
                                    data-hover_dept_bgcolor='@json($dept_hover_bgcolor_array)'
                                    data-dept_emp_count='@json($dept_count_array)'
                                    data-dept_label='@json($dept_name_array)'></canvas>
                            </div>
                            <div class="hr-chart-legend-scroll" id="department_chart_legend" aria-label="{{ __('Department legend') }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="hr-panel h-100">
                        <div class="hr-panel__header">
                            <h4 class="hr-panel__title mb-0">{{ __('Project Status') }}</h4>
                            <p class="hr-panel__subtitle mb-0">{{ __('Portfolio breakdown') }}</p>
                        </div>
                        <div class="hr-panel__body">
                            <div class="hr-chart-wrap hr-chart-wrap--wide">
                                <canvas id="project_chart"
                                    data-project_status='@json($project_count_array)'
                                    data-project_label='@json($project_name_array)'
                                    data-project_color='@json($project_color_array)'></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="hr-panel h-100">
                        <div class="hr-panel__header">
                            <h4 class="hr-panel__title mb-0">{{ __('Quick Snapshot') }}</h4>
                            <p class="hr-panel__subtitle mb-0">{{ __('Key metrics at a glance') }}</p>
                        </div>
                        <div class="hr-panel__body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="hr-snapshot">
                                        <div class="hr-snapshot__value">{{ $attendanceRate }}%</div>
                                        <div class="hr-snapshot__label">{{ __('Attendance Rate') }}</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="hr-snapshot">
                                        <div class="hr-snapshot__value">{{ $leave_count }}</div>
                                        <div class="hr-snapshot__label">{{ __('On Leave') }}</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="hr-snapshot">
                                        <div class="hr-snapshot__value">{{ $ticket_count }}</div>
                                        <div class="hr-snapshot__label">{{ __('Open Tickets') }}</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="hr-snapshot">
                                        <div class="hr-snapshot__value">{{ $completed_projects }}</div>
                                        <div class="hr-snapshot__label">{{ __('Completed') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                @include('calendarable.calendar')
            </div>
        </div>
    </section>

@endsection

@push('css')
<style>
    .hr-dashboard {
        --hr-primary: #37205b;
        --hr-violet: #5b4a9a;
        --hr-violet-light: #7c5cc4;
        --hr-cyan: #19aed9;
        --hr-amber: #f59e0b;
        --hr-emerald: #10b981;
        --hr-ink: #1e293b;
        --hr-muted: #64748b;
        --hr-border: #e2e8f0;
    }

    .hr-dashboard .hr-hero {
        background: linear-gradient(135deg, #37205b 0%, #5b4a9a 55%, #7c5cc4 100%);
        color: #fff;
        border-radius: 16px;
        box-shadow: 0 18px 40px rgba(55, 32, 91, 0.18);
    }

    .hr-dashboard .hr-hero__eyebrow {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.85;
        margin-bottom: 6px;
    }

    .hr-dashboard .hr-hero__title { font-size: 1.65rem; font-weight: 700; }
    .hr-dashboard .hr-hero__subtitle { opacity: 0.9; font-size: 0.92rem; }
    .hr-dashboard .hr-hero__day { font-size: 1rem; font-weight: 600; }
    .hr-dashboard .hr-hero__full-date { font-size: 0.88rem; opacity: 0.9; }

    .hr-dashboard .hr-kpi {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #fff;
        border: 1px solid var(--hr-border);
        border-radius: 14px;
        padding: 18px;
        height: 100%;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .hr-dashboard .hr-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        text-decoration: none;
        color: inherit;
    }

    .hr-dashboard .hr-kpi__icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .hr-dashboard .hr-kpi--violet .hr-kpi__icon { background: rgba(91, 74, 154, 0.12); color: var(--hr-violet); }
    .hr-dashboard .hr-kpi--indigo .hr-kpi__icon { background: rgba(55, 32, 91, 0.12); color: var(--hr-primary); }
    .hr-dashboard .hr-kpi--cyan .hr-kpi__icon { background: rgba(25, 174, 217, 0.12); color: var(--hr-cyan); }
    .hr-dashboard .hr-kpi--amber .hr-kpi__icon { background: rgba(245, 158, 11, 0.12); color: var(--hr-amber); }
    .hr-dashboard .hr-kpi--emerald .hr-kpi__icon { background: rgba(16, 185, 129, 0.12); color: var(--hr-emerald); }
    .hr-dashboard .hr-kpi--rose .hr-kpi__icon { background: rgba(244, 96, 91, 0.12); color: #f4605b; }

    .hr-dashboard .hr-kpi__label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--hr-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .hr-dashboard .hr-kpi__value {
        font-size: 1.45rem;
        font-weight: 700;
        color: var(--hr-ink);
        line-height: 1.2;
    }

    .hr-dashboard .hr-kpi__meta { font-size: 0.8rem; color: var(--hr-muted); }

    .hr-dashboard .hr-panel {
        background: #fff;
        border: 1px solid var(--hr-border);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .hr-dashboard .hr-panel__header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--hr-border);
        background: linear-gradient(180deg, #fafbfe 0%, #fff 100%);
    }

    .hr-dashboard .hr-panel__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--hr-ink);
    }

    .hr-dashboard .hr-panel__subtitle {
        font-size: 0.82rem;
        color: var(--hr-muted);
        margin-top: 2px;
    }

    .hr-dashboard .hr-panel__body { padding: 18px 20px; }

    .hr-dashboard .hr-chart-wrap {
        position: relative;
        width: 100%;
        min-height: 200px;
    }

    .hr-dashboard .hr-chart-wrap--wide {
        min-height: 220px;
    }

    .hr-dashboard .hr-chart-legend-scroll {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px 12px;
        max-height: 120px;
        overflow-y: auto;
        margin-top: 12px;
        padding-right: 4px;
    }

    .hr-dashboard .hr-chart-legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.72rem;
        color: var(--hr-muted);
        min-width: 0;
    }

    .hr-dashboard .hr-chart-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .hr-dashboard .hr-chart-legend-text {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .hr-dashboard .hr-chart-legend-count {
        font-weight: 700;
        color: var(--hr-ink);
        flex-shrink: 0;
    }

    @media (max-width: 991px) {
        .hr-dashboard .hr-chart-legend-scroll {
            grid-template-columns: 1fr;
            max-height: 140px;
        }
    }

    .hr-dashboard .hr-attendance-stats {
        display: flex;
        justify-content: center;
        gap: 24px;
        font-size: 0.88rem;
        color: var(--hr-muted);
    }

    .hr-dashboard .hr-attendance-stat {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .hr-dashboard .hr-attendance-stat__dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .hr-dashboard .hr-attendance-stat__dot--present { background: #10b981; }
    .hr-dashboard .hr-attendance-stat__dot--absent { background: #94a3b8; }

    .hr-dashboard .hr-snapshot {
        background: #f8fafc;
        border: 1px solid var(--hr-border);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        height: 100%;
    }

    .hr-dashboard .hr-snapshot__value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--hr-primary);
    }

    .hr-dashboard .hr-snapshot__label {
        font-size: 0.78rem;
        color: var(--hr-muted);
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
</style>
@endpush
