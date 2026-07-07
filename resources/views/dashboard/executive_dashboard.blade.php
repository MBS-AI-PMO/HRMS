@extends('layout.main')
@section('content')

    @php
        $kpiCards = [
            [
                'label' => trans('file.Employees'),
                'value' => $metrics['totals']['employees'],
                'growth' => $metrics['growth']['employees'],
                'sub' => $metrics['growth']['new_employees_this_month'].' '.__('new this month'),
                'icon' => 'dripicons-user-group',
                'tone' => 'violet',
                'route' => route('employees.index'),
            ],
            [
                'label' => trans('file.Company'),
                'value' => $metrics['totals']['companies'],
                'growth' => $metrics['growth']['companies'],
                'sub' => $metrics['growth']['new_companies_this_month'].' '.__('new this month'),
                'icon' => 'dripicons-store',
                'tone' => 'indigo',
                'route' => route('companies.index'),
            ],
            [
                'label' => trans('file.Client'),
                'value' => $metrics['totals']['clients'],
                'growth' => $metrics['growth']['clients'],
                'sub' => $metrics['totals']['active_clients'].' '.__('external accounts'),
                'icon' => 'dripicons-briefcase',
                'tone' => 'cyan',
                'route' => route('clients.index'),
            ],
            [
                'label' => trans('file.Projects'),
                'value' => $metrics['totals']['projects'],
                'growth' => $metrics['growth']['projects'],
                'sub' => $metrics['totals']['running_projects'].' '.__('running'),
                'icon' => 'dripicons-checklist',
                'tone' => 'amber',
                'route' => route('projects.index'),
            ],
            [
                'label' => trans('file.Location'),
                'value' => $metrics['totals']['locations'],
                'growth' => null,
                'sub' => $metrics['totals']['locations_with_client'].' '.__('client-linked sites'),
                'icon' => 'dripicons-location',
                'tone' => 'emerald',
                'route' => route('locations.index'),
            ],
        ];

        $projectStatusTotal = array_sum($metrics['charts']['project_status']);
    @endphp

    <section class="exec-dashboard">
        <div class="container-fluid">
            <div class="exec-hero card border-0 mb-4">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <span class="exec-hero__eyebrow">{{ __('Business Intelligence') }}</span>
                        <h1 class="exec-hero__title mb-1">{{ __('Executive Dashboard') }}</h1>
                        <p class="exec-hero__subtitle mb-0">{{ __('Overall business growth and workforce overview') }}</p>
                    </div>
                    <div class="exec-hero__date text-right">
                        <div class="exec-hero__day">{{ now()->englishDayOfWeek }}</div>
                        <div class="exec-hero__full-date">{{ now()->format(env('Date_Format')) }}</div>
                        <a href="{{ route('report.summary-dashboard') }}" class="btn btn-light btn-sm mt-2 exec-hero__cta">
                            <i class="dripicons-graph-line"></i> {{ __('Operations Summary') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                @foreach ($kpiCards as $card)
                    <div class="col-sm-6 col-md-4 col-xl mb-4">
                        <a href="{{ $card['route'] }}" class="exec-kpi exec-kpi--{{ $card['tone'] }}">
                            <div class="exec-kpi__icon"><i class="{{ $card['icon'] }}"></i></div>
                            <div class="exec-kpi__content">
                                <span class="exec-kpi__label">{{ $card['label'] }}</span>
                                <div class="exec-kpi__value-row">
                                    <span class="exec-kpi__value">{{ number_format($card['value']) }}</span>
                                    @if (! is_null($card['growth']))
                                        <span class="exec-kpi__growth exec-kpi__growth--{{ $card['growth'] >= 0 ? 'up' : 'down' }}">
                                            <i class="dripicons-{{ $card['growth'] >= 0 ? 'chevron-up' : 'chevron-down' }}"></i>
                                            {{ $card['growth'] >= 0 ? '+' : '' }}{{ $card['growth'] }}%
                                        </span>
                                    @endif
                                </div>
                                <span class="exec-kpi__meta">{{ $card['sub'] }}</span>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="row mb-2">
                <div class="col-md-4 mb-4">
                    <div class="exec-stat-pill">
                        <div class="exec-stat-pill__icon exec-stat-pill__icon--orange"><i class="dripicons-clock"></i></div>
                        <div>
                            <div class="exec-stat-pill__label">{{ trans('file.Attendance') }}</div>
                            <div class="exec-stat-pill__value">{{ $metrics['totals']['attendance_rate'] }}%</div>
                            <div class="exec-stat-pill__meta">{{ $metrics['totals']['attendance_today'] }} / {{ $metrics['totals']['employees'] }} {{ __('present today') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="exec-stat-pill">
                        <div class="exec-stat-pill__icon exec-stat-pill__icon--green"><i class="dripicons-calendar"></i></div>
                        <div>
                            <div class="exec-stat-pill__label">{{ __('Pending Leave') }}</div>
                            <div class="exec-stat-pill__value">{{ $metrics['totals']['pending_leaves'] }}</div>
                            <div class="exec-stat-pill__meta">{{ __('Awaiting approval') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="exec-stat-pill">
                        <div class="exec-stat-pill__icon exec-stat-pill__icon--blue"><i class="dripicons-ticket"></i></div>
                        <div>
                            <div class="exec-stat-pill__label">{{ __('Open Complaints') }}</div>
                            <div class="exec-stat-pill__value">{{ $metrics['totals']['open_tickets'] }}</div>
                            <div class="exec-stat-pill__meta">{{ __('Requires attention') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="exec-panel h-100">
                        <div class="exec-panel__header">
                            <div>
                                <h4 class="exec-panel__title">{{ __('Growth Trend') }}</h4>
                                <p class="exec-panel__subtitle mb-0">{{ __('Last 6 months — employees, clients, and projects') }}</p>
                            </div>
                        </div>
                        <div class="exec-panel__body">
                            <div class="exec-chart-wrap">
                                <canvas id="executive_growth_chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="exec-panel h-100">
                        <div class="exec-panel__header">
                            <div>
                                <h4 class="exec-panel__title">{{ __('Project Status') }}</h4>
                                <p class="exec-panel__subtitle mb-0">{{ __('Current portfolio breakdown') }}</p>
                            </div>
                        </div>
                        <div class="exec-panel__body exec-panel__body--chart">
                            @if ($projectStatusTotal > 0)
                                <div class="exec-chart-wrap exec-chart-wrap--compact">
                                    <canvas id="executive_project_status_chart"></canvas>
                                </div>
                            @else
                                <div class="exec-empty-state">
                                    <i class="dripicons-information"></i>
                                    <p>{{ __('No project status data yet.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="exec-panel h-100">
                        <div class="exec-panel__header">
                            <div>
                                <h4 class="exec-panel__title">{{ __('Company → Client → Project') }}</h4>
                                <p class="exec-panel__subtitle mb-0">{{ __('See how many clients and projects belong to each company') }}</p>
                            </div>
                        </div>
                        <div class="exec-panel__body exec-hierarchy-scroll" id="exec_hierarchy_list">
                            @forelse ($metrics['company_hierarchy'] as $company)
                                <div class="exec-hierarchy-item exec-hierarchy-company-root">
                                    <div class="exec-hierarchy-row exec-hierarchy-row--company"
                                         data-toggle="collapse"
                                         data-target="#exec_company_{{ $company['id'] }}"
                                         aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                         style="cursor:pointer;">
                                        <i class="dripicons-chevron-right exec-hierarchy-chevron"></i>
                                        <span class="exec-hierarchy-name">{{ $company['name'] }}</span>
                                        <span class="exec-type-badge exec-type-badge--company ml-2">{{ trans('file.Company') }}</span>
                                        <span class="exec-hierarchy-meta ml-auto">
                                            {{ $company['clients_count'] }} {{ trans('file.Client') }}
                                            · {{ $company['total_projects'] }} {{ trans('file.Projects') }}
                                        </span>
                                    </div>
                                    <div id="exec_company_{{ $company['id'] }}" class="collapse exec-hierarchy-children{{ $loop->first ? ' show' : '' }}">
                                        @forelse ($company['clients'] as $client)
                                            <div class="exec-hierarchy-item exec-hierarchy-item--nested">
                                                <div class="exec-hierarchy-row exec-hierarchy-row--client"
                                                     data-toggle="collapse"
                                                     data-target="#exec_client_{{ $company['id'] }}_{{ $client['id'] }}"
                                                     aria-expanded="false"
                                                     style="cursor:pointer;">
                                                    <i class="dripicons-chevron-right exec-hierarchy-chevron"></i>
                                                    <span class="exec-hierarchy-name">{{ $client['name'] }}</span>
                                                    <span class="exec-type-badge exec-type-badge--client ml-2">{{ trans('file.Client') }}</span>
                                                    <span class="exec-hierarchy-meta ml-auto">{{ $client['projects_count'] }} {{ trans('file.Projects') }}</span>
                                                </div>
                                                <div id="exec_client_{{ $company['id'] }}_{{ $client['id'] }}" class="collapse exec-hierarchy-children exec-hierarchy-children--projects">
                                                    @forelse ($client['projects'] as $project)
                                                        <div class="exec-hierarchy-project">
                                                            <span class="exec-hierarchy-project__title">{{ $project['title'] }}</span>
                                                            <span class="exec-badge exec-badge--muted">{{ $project['status_label'] }}</span>
                                                        </div>
                                                    @empty
                                                        <div class="exec-hierarchy-project exec-hierarchy-project--empty text-muted">{{ __('No projects') }}</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @empty
                                            <div class="exec-hierarchy-empty text-muted">{{ __('No linked clients') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="exec-empty-state">
                                    <i class="dripicons-network-3"></i>
                                    <p>{{ __('No company hierarchy data found.') }}</p>
                                </div>
                            @endforelse
                        </div>
                        <div id="exec_hierarchy_pagination" class="exec-hierarchy-pagination px-3 pb-3"></div>
                    </div>
                </div>
                <div class="col-lg-5 mb-4">
                    <div class="exec-panel h-100">
                        <div class="exec-panel__header">
                            <div>
                                <h4 class="exec-panel__title">{{ __('Largest Departments') }}</h4>
                                <p class="exec-panel__subtitle mb-0">{{ __('Active headcount by department') }}</p>
                            </div>
                        </div>
                        <div class="exec-panel__body exec-panel__body--chart">
                            @if ($metrics['department_headcount']->isNotEmpty())
                                <div class="exec-chart-wrap exec-chart-wrap--compact">
                                    <canvas id="executive_department_chart"></canvas>
                                </div>
                            @else
                                <div class="exec-empty-state">
                                    <i class="dripicons-network-3"></i>
                                    <p>{{ __('No department data available.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('css')
<style>
    .exec-dashboard {
        --exec-violet: #5b4a9a;
        --exec-cyan: #0ea5e9;
        --exec-amber: #f59e0b;
        --exec-emerald: #10b981;
        --exec-ink: #1e293b;
        --exec-muted: #64748b;
        --exec-border: #e2e8f0;
        --exec-surface: #ffffff;
        --exec-bg: #f4f6fb;
    }

    .exec-dashboard .exec-hero {
        background: linear-gradient(135deg, #37205b 0%, #5b4a9a 55%, #7c5cc4 100%);
        color: #fff;
        border-radius: 16px;
        box-shadow: 0 18px 40px rgba(55, 32, 91, 0.18);
        overflow: hidden;
    }

    .exec-dashboard .exec-hero__eyebrow {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        opacity: 0.85;
        margin-bottom: 6px;
    }

    .exec-dashboard .exec-hero__title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #fff;
    }

    .exec-dashboard .exec-hero__subtitle {
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.95rem;
    }

    .exec-dashboard .exec-hero__day {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .exec-dashboard .exec-hero__full-date {
        font-size: 0.9rem;
        opacity: 0.85;
    }

    .exec-dashboard .exec-hero__cta {
        border-radius: 999px;
        font-weight: 600;
        color: var(--exec-violet);
    }

    .exec-dashboard .exec-kpi {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        background: var(--exec-surface);
        border: 1px solid var(--exec-border);
        border-radius: 16px;
        padding: 20px;
        height: 100%;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .exec-dashboard .exec-kpi:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        text-decoration: none;
        color: inherit;
    }

    .exec-dashboard .exec-kpi--indigo { border-top: 4px solid #4f46e5; }
    .exec-dashboard .exec-kpi--indigo .exec-kpi__icon { background: rgba(79, 70, 229, 0.12); color: #4f46e5; }

    .exec-dashboard .exec-kpi--violet { border-top: 4px solid var(--exec-violet); }
    .exec-dashboard .exec-kpi--cyan { border-top: 4px solid var(--exec-cyan); }
    .exec-dashboard .exec-kpi--amber { border-top: 4px solid var(--exec-amber); }
    .exec-dashboard .exec-kpi--emerald { border-top: 4px solid var(--exec-emerald); }

    .exec-dashboard .exec-kpi__icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }

    .exec-dashboard .exec-kpi--violet .exec-kpi__icon { background: rgba(91, 74, 154, 0.12); color: var(--exec-violet); }
    .exec-dashboard .exec-kpi--cyan .exec-kpi__icon { background: rgba(14, 165, 233, 0.12); color: var(--exec-cyan); }
    .exec-dashboard .exec-kpi--amber .exec-kpi__icon { background: rgba(245, 158, 11, 0.14); color: var(--exec-amber); }
    .exec-dashboard .exec-kpi--emerald .exec-kpi__icon { background: rgba(16, 185, 129, 0.14); color: var(--exec-emerald); }

    .exec-dashboard .exec-kpi__label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--exec-muted);
        margin-bottom: 4px;
    }

    .exec-dashboard .exec-kpi__value-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 4px;
    }

    .exec-dashboard .exec-kpi__value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
        color: var(--exec-ink);
    }

    .exec-dashboard .exec-kpi__growth {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 2px;
    }

    .exec-dashboard .exec-kpi__growth--up {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
    }

    .exec-dashboard .exec-kpi__growth--down {
        background: rgba(239, 68, 68, 0.12);
        color: #dc2626;
    }

    .exec-dashboard .exec-kpi__meta {
        font-size: 0.82rem;
        color: var(--exec-muted);
    }

    .exec-dashboard .exec-stat-pill {
        display: flex;
        gap: 14px;
        align-items: center;
        background: var(--exec-surface);
        border: 1px solid var(--exec-border);
        border-radius: 14px;
        padding: 18px 20px;
        height: 100%;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }

    .exec-dashboard .exec-stat-pill__icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .exec-dashboard .exec-stat-pill__icon--orange { background: rgba(245, 158, 11, 0.12); color: #d97706; }
    .exec-dashboard .exec-stat-pill__icon--green { background: rgba(16, 185, 129, 0.12); color: #059669; }
    .exec-dashboard .exec-stat-pill__icon--blue { background: rgba(14, 165, 233, 0.12); color: #0284c7; }

    .exec-dashboard .exec-stat-pill__label {
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--exec-muted);
    }

    .exec-dashboard .exec-stat-pill__value {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--exec-ink);
        line-height: 1.2;
    }

    .exec-dashboard .exec-stat-pill__meta {
        font-size: 0.82rem;
        color: var(--exec-muted);
    }

    .exec-dashboard .exec-panel {
        background: var(--exec-surface);
        border: 1px solid var(--exec-border);
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .exec-dashboard .exec-panel__header {
        padding: 20px 24px 0;
    }

    .exec-dashboard .exec-panel__title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--exec-ink);
        margin-bottom: 2px;
    }

    .exec-dashboard .exec-panel__subtitle {
        font-size: 0.84rem;
        color: var(--exec-muted);
    }

    .exec-dashboard .exec-panel__body {
        padding: 16px 24px 24px;
    }

    .exec-dashboard .exec-panel__body--chart {
        min-height: 260px;
    }

    .exec-dashboard .exec-chart-wrap {
        position: relative;
        width: 100%;
        min-height: 240px;
    }

    .exec-dashboard .exec-chart-wrap--compact {
        min-height: 220px;
    }

    .exec-dashboard .exec-empty-state {
        text-align: center;
        color: var(--exec-muted);
        padding: 24px;
    }

    .exec-dashboard .exec-empty-state i {
        font-size: 2rem;
        display: block;
        margin-bottom: 10px;
        opacity: 0.5;
    }

    .exec-dashboard .exec-table thead th {
        border-top: 0;
        border-bottom: 1px solid var(--exec-border);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--exec-muted);
        padding: 14px 20px;
        background: #f8fafc;
    }

    .exec-dashboard .exec-table tbody td {
        padding: 14px 20px;
        vertical-align: middle;
        border-color: #f1f5f9;
    }

    .exec-dashboard .exec-table__name {
        font-weight: 600;
        color: var(--exec-ink);
    }

    .exec-dashboard .exec-table__count {
        display: inline-flex;
        min-width: 32px;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 999px;
        background: rgba(91, 74, 154, 0.1);
        color: var(--exec-violet);
        font-weight: 700;
        font-size: 0.85rem;
    }

    .exec-dashboard .exec-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .exec-dashboard .exec-badge--success {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
    }

    .exec-dashboard .exec-badge--muted {
        background: #f1f5f9;
        color: #64748b;
    }

    .exec-dashboard .exec-empty-row {
        color: var(--exec-muted);
        padding: 28px !important;
    }

    .exec-dashboard .exec-type-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .exec-dashboard .exec-type-badge--company {
        background: rgba(79, 70, 229, 0.12);
        color: #4f46e5;
    }

    .exec-dashboard .exec-type-badge--client {
        background: rgba(14, 165, 233, 0.12);
        color: #0284c7;
    }

    .exec-dashboard .exec-hierarchy-scroll {
        max-height: 520px;
        overflow-y: auto;
        padding: 12px 16px;
    }

    .exec-dashboard .exec-hierarchy-item {
        border: 1px solid var(--exec-border);
        border-radius: 10px;
        margin-bottom: 10px;
        overflow: hidden;
        background: var(--exec-surface);
    }

    .exec-dashboard .exec-hierarchy-item--nested {
        border: none;
        border-radius: 0;
        margin: 0;
        border-top: 1px solid var(--exec-border);
    }

    .exec-dashboard .exec-hierarchy-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 14px;
        transition: background 0.15s ease;
    }

    .exec-dashboard .exec-hierarchy-row:hover {
        background: rgba(91, 74, 154, 0.04);
    }

    .exec-dashboard .exec-hierarchy-row--company {
        background: rgba(79, 70, 229, 0.04);
        font-weight: 600;
    }

    .exec-dashboard .exec-hierarchy-row--client {
        padding-left: 28px;
        font-weight: 500;
    }

    .exec-dashboard .exec-hierarchy-chevron {
        font-size: 0.75rem;
        color: var(--exec-muted);
        transition: transform 0.2s ease;
        flex-shrink: 0;
    }

    .exec-dashboard .exec-hierarchy-row[aria-expanded="true"] .exec-hierarchy-chevron {
        transform: rotate(90deg);
    }

    .exec-dashboard .exec-hierarchy-name {
        color: var(--exec-ink);
    }

    .exec-dashboard .exec-hierarchy-meta {
        font-size: 0.8rem;
        color: var(--exec-muted);
        white-space: nowrap;
    }

    .exec-dashboard .exec-hierarchy-children {
        border-top: 1px solid var(--exec-border);
    }

    .exec-dashboard .exec-hierarchy-children--projects {
        background: #f8fafc;
        padding: 6px 14px 10px 44px;
        border-top: none;
    }

    .exec-dashboard .exec-hierarchy-project {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 7px 0;
        border-bottom: 1px dashed rgba(148, 163, 184, 0.35);
        font-size: 0.88rem;
    }

    .exec-dashboard .exec-hierarchy-project:last-child {
        border-bottom: none;
    }

    .exec-dashboard .exec-hierarchy-project__title {
        color: var(--exec-ink);
    }

    .exec-dashboard .exec-hierarchy-empty {
        padding: 12px 28px;
        font-size: 0.85rem;
    }

    .exec-dashboard .exec-hierarchy-pagination .pagination {
        margin-bottom: 0;
    }

    .exec-dashboard .exec-hierarchy-pagination .page-link {
        color: var(--exec-violet);
        border-color: var(--exec-border);
        font-size: 0.82rem;
        padding: 0.35rem 0.65rem;
    }

    .exec-dashboard .exec-hierarchy-pagination .page-item.active .page-link {
        background: var(--exec-violet);
        border-color: var(--exec-violet);
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript">
    (function ($) {
        'use strict';

        const hierarchyPerPage = 3;
        let hierarchyCurrentPage = 1;

        function renderExecHierarchyPagination(totalPages) {
            const $pagination = $('#exec_hierarchy_pagination');

            if (totalPages <= 1) {
                $pagination.empty();
                return;
            }

            let html = '<nav aria-label="Company hierarchy pages"><ul class="pagination pagination-sm justify-content-center mb-0">';
            html += '<li class="page-item' + (hierarchyCurrentPage === 1 ? ' disabled' : '') + '">';
            html += '<a class="page-link" href="#" data-page="' + (hierarchyCurrentPage - 1) + '">' + @json(__('Previous')) + '</a></li>';

            for (let page = 1; page <= totalPages; page++) {
                html += '<li class="page-item' + (page === hierarchyCurrentPage ? ' active' : '') + '">';
                html += '<a class="page-link" href="#" data-page="' + page + '">' + page + '</a></li>';
            }

            html += '<li class="page-item' + (hierarchyCurrentPage === totalPages ? ' disabled' : '') + '">';
            html += '<a class="page-link" href="#" data-page="' + (hierarchyCurrentPage + 1) + '">' + @json(__('Next')) + '</a></li>';
            html += '</ul></nav>';

            $pagination.html(html);
        }

        function showExecHierarchyPage(page) {
            const $items = $('#exec_hierarchy_list .exec-hierarchy-company-root');
            const total = $items.length;
            const totalPages = Math.max(1, Math.ceil(total / hierarchyPerPage));

            hierarchyCurrentPage = Math.min(Math.max(1, page), totalPages);
            const start = (hierarchyCurrentPage - 1) * hierarchyPerPage;
            const end = start + hierarchyPerPage;

            $items.each(function (index) {
                $(this).toggle(index >= start && index < end);
            });

            renderExecHierarchyPagination(totalPages);
        }

        $(document).on('click', '#exec_hierarchy_pagination .page-link', function (event) {
            event.preventDefault();
            const $item = $(this).parent();
            if ($item.hasClass('disabled') || $item.hasClass('active')) {
                return;
            }
            const page = parseInt($(this).data('page'), 10);
            if (!isNaN(page)) {
                showExecHierarchyPage(page);
            }
        });

        $(document).ready(function () {
            showExecHierarchyPage(1);
        });
    })(jQuery);
</script>
<script type="text/javascript">
    (function () {
        'use strict';

        const metrics = @json($metrics);
        const chartFont = "'Segoe UI', system-ui, -apple-system, sans-serif";
        const gridColor = 'rgba(148, 163, 184, 0.2)';

        if (typeof Chart === 'undefined') {
            return;
        }

        Chart.defaults.global.defaultFontFamily = chartFont;
        Chart.defaults.global.defaultFontColor = '#64748b';

        const growthCtx = document.getElementById('executive_growth_chart');
        if (growthCtx) {
            new Chart(growthCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: metrics.charts.month_labels,
                    datasets: [
                        {
                            label: @json(trans('file.Employees')),
                            data: metrics.charts.employee_growth,
                            borderColor: '#5b4a9a',
                            backgroundColor: 'rgba(91, 74, 154, 0.08)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#5b4a9a',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            lineTension: 0.35,
                        },
                        {
                            label: @json(trans('file.Company')),
                            data: metrics.charts.company_growth,
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.08)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#4f46e5',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            lineTension: 0.35,
                        },
                        {
                            label: @json(trans('file.Client')),
                            data: metrics.charts.client_growth,
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14, 165, 233, 0.08)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#0ea5e9',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            lineTension: 0.35,
                        },
                        {
                            label: @json(trans('file.Projects')),
                            data: metrics.charts.project_growth,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.08)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            lineTension: 0.35,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2.4,
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 16, fontStyle: '600' },
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1e293b',
                        titleFontStyle: '600',
                        bodyFontStyle: '500',
                        cornerRadius: 8,
                        xPadding: 12,
                        yPadding: 10,
                    },
                    scales: {
                        xAxes: [{
                            gridLines: { display: false },
                            ticks: { fontStyle: '600' },
                        }],
                        yAxes: [{
                            gridLines: { color: gridColor, drawBorder: false },
                            ticks: { beginAtZero: true, precision: 0, padding: 8 },
                        }],
                    },
                },
            });
        }

        const statusCtx = document.getElementById('executive_project_status_chart');
        if (statusCtx) {
            const status = metrics.charts.project_status;
            new Chart(statusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: [
                        @json(__('In Progress')),
                        @json(__('Not Started')),
                        @json(__('Completed')),
                        @json(__('Deferred')),
                    ],
                    datasets: [{
                        data: [
                            status.in_progress,
                            status.not_started,
                            status.completed,
                            status.deferred,
                        ],
                        backgroundColor: ['#5b4a9a', '#c4b5fd', '#0ea5e9', '#f87171'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.2,
                    cutoutPercentage: 68,
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 14, fontStyle: '600' },
                    },
                    tooltips: {
                        backgroundColor: '#1e293b',
                        cornerRadius: 8,
                    },
                },
            });
        }

        const deptCtx = document.getElementById('executive_department_chart');
        if (deptCtx && metrics.department_headcount.length) {
            new Chart(deptCtx.getContext('2d'), {
                type: 'horizontalBar',
                data: {
                    labels: metrics.department_headcount.map(function (item) { return item.department; }),
                    datasets: [{
                        label: @json(trans('file.Employees')),
                        data: metrics.department_headcount.map(function (item) { return item.total; }),
                        backgroundColor: 'rgba(91, 74, 154, 0.85)',
                        borderColor: '#5b4a9a',
                        borderWidth: 0,
                        barPercentage: 0.65,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.1,
                    legend: { display: false },
                    tooltips: {
                        backgroundColor: '#1e293b',
                        cornerRadius: 8,
                    },
                    scales: {
                        xAxes: [{
                            gridLines: { color: gridColor, drawBorder: false },
                            ticks: { beginAtZero: true, precision: 0 },
                        }],
                        yAxes: [{
                            gridLines: { display: false },
                            ticks: { fontStyle: '600' },
                        }],
                    },
                },
            });
        }
    })();
</script>
@endpush
