@extends('layout.main')
@section('content')

    <section class="ops-summary">
        <div class="container-fluid">
            <div class="ops-hero card border-0 mb-4">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <span class="ops-hero__eyebrow">{{ __('Operations Intelligence') }}</span>
                        <h1 class="ops-hero__title mb-1">{{ __('Operations Summary Dashboard') }}</h1>
                        <p class="ops-hero__subtitle mb-0">{{ __('Company → Client → Project → Deployed employee overview') }}</p>
                    </div>
                    <div class="ops-hero__actions text-right ops-no-print">
                        <div class="ops-hero__date">{{ now()->format(env('Date_Format')) }}</div>
                        <div class="btn-group btn-group-sm mt-2" role="group">
                            <a href="#" class="btn btn-light" id="export_summary_pdf" title="{{ __('Download PDF report') }}">
                                <i class="fa fa-file-pdf-o"></i> PDF
                            </a>
                            <a href="#" class="btn btn-light" id="export_summary_csv" title="{{ __('Download CSV') }}">
                                <i class="fa fa-file-text-o"></i> CSV
                            </a>
                            <button type="button" class="btn btn-light" id="print_summary_dashboard" title="{{ __('Print') }}">
                                <i class="fa fa-print"></i> {{ __('Print') }}
                            </button>
                            <button type="button" class="btn btn-light" id="refresh_summary_dashboard" title="{{ __('Refresh') }}">
                                <i class="dripicons-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ops-panel mb-4 ops-no-print">
                <div class="ops-panel__header">
                    <h3 class="ops-panel__title mb-0"><i class="dripicons-filter"></i> {{ __('Filters') }}</h3>
                </div>
                <div class="ops-panel__body">
                    <form id="summary_filter_form" class="row align-items-end">
                        <div class="col-md-2">
                            <div class="form-group mb-md-0">
                                <label class="ops-label">{{ trans('file.Company') }}</label>
                                <select id="filter_company_id" class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains"
                                    title="{{ __('All') }}">
                                    <option value="">{{ __('All') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-md-0">
                                <label class="ops-label">{{ trans('file.Client') }}</label>
                                <select id="filter_client_id" class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains"
                                    title="{{ __('All') }}">
                                    <option value="">{{ __('All') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-md-0">
                                <label class="ops-label">{{ trans('file.Department') }}</label>
                                <select id="filter_department_id" class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains"
                                    title="{{ __('All') }}">
                                    <option value="">{{ __('All') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-md-0">
                                <label class="ops-label">{{ trans('file.Location') }}</label>
                                <select id="filter_location_id" class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains"
                                    title="{{ __('All') }}">
                                    <option value="">{{ __('All') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-md-0">
                                <label class="ops-label">{{ trans('file.Status') }}</label>
                                <select id="filter_project_status" class="form-control selectpicker"
                                    title="{{ __('All') }}">
                                    <option value="">{{ __('All') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-filter"></i> {{ trans('file.Search') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-block mt-2" id="reset_summary_filters">
                                {{ __('Reset') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="summary_loading" class="ops-loading text-center py-5">
                <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-3 text-muted mb-0">{{ __('Loading summary...') }}</p>
            </div>

            <div id="summary_content" style="display:none;">
                <div class="row" id="summary_stat_cards"></div>

                <div class="row mt-2" id="summary_charts_row">
                    <div class="col-lg-4 mb-4">
                        <div class="ops-panel h-100">
                            <div class="ops-panel__header">
                                <h4 class="ops-panel__title mb-0">{{ __('Project Status') }}</h4>
                                <p class="ops-panel__subtitle mb-0">{{ __('Portfolio breakdown') }}</p>
                            </div>
                            <div class="ops-panel__body">
                                <div class="ops-chart-wrap">
                                    <canvas id="summary_project_status_chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="ops-panel h-100">
                            <div class="ops-panel__header">
                                <h4 class="ops-panel__title mb-0">{{ __('Operations Pipeline') }}</h4>
                                <p class="ops-panel__subtitle mb-0">{{ __('Companies → deployed staff') }}</p>
                            </div>
                            <div class="ops-panel__body">
                                <div class="ops-chart-wrap">
                                    <canvas id="summary_pipeline_chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="ops-panel h-100">
                            <div class="ops-panel__header">
                                <h4 class="ops-panel__title mb-0">{{ __('Deployment by Company') }}</h4>
                                <p class="ops-panel__subtitle mb-0">{{ __('Employees per organization') }}</p>
                            </div>
                            <div class="ops-panel__body">
                                <div class="ops-chart-wrap">
                                    <canvas id="summary_deployment_company_chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ops-panel mb-4">
                    <div class="ops-panel__header">
                        <h4 class="ops-panel__title mb-0">{{ __('Company → Client → Project → Employee') }}</h4>
                        <p class="ops-panel__subtitle mb-0">{{ __('Expand each level to view deployment details') }}</p>
                    </div>
                    <div class="ops-panel__body">
                        <div id="company_breakdown"></div>
                        <div id="company_breakdown_pagination"></div>
                    </div>
                </div>

                <div class="ops-panel">
                    <div class="ops-panel__header">
                        <h4 class="ops-panel__title mb-0">{{ __('Resource Deployment Matrix') }}</h4>
                        <p class="ops-panel__subtitle mb-0">{{ __('Searchable view of employee assignments') }}</p>
                    </div>
                    <div class="ops-panel__body">
                        <div class="table-responsive">
                            <table id="deployment_table" class="table table-hover ops-table mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ trans('file.Employee') }}</th>
                                        <th>{{ __('Staff ID') }}</th>
                                        <th>{{ trans('file.Company') }}</th>
                                        <th>{{ trans('file.Client') }}</th>
                                        <th>{{ trans('file.Location') }}</th>
                                        <th>{{ trans('file.Department') }}</th>
                                        <th>{{ trans('file.Designation') }}</th>
                                        <th>{{ trans('file.Project') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="deployment_table_body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('css')
<style>
    .ops-summary {
        --ops-primary: #37205b;
        --ops-violet: #5b4a9a;
        --ops-violet-light: #7c5cc4;
        --ops-indigo: #5b4a9a;
        --ops-cyan: #19aed9;
        --ops-amber: #f59e0b;
        --ops-emerald: #10b981;
        --ops-ink: #1e293b;
        --ops-muted: #64748b;
        --ops-border: #e2e8f0;
        --ops-surface: #ffffff;
        --ops-bg: #f4f6fb;
    }

    .ops-summary .ops-hero {
        background: linear-gradient(135deg, #37205b 0%, #5b4a9a 55%, #7c5cc4 100%);
        color: #fff;
        border-radius: 16px;
        box-shadow: 0 18px 40px rgba(55, 32, 91, 0.18);
        overflow: hidden;
    }

    .ops-summary .ops-hero__eyebrow {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.85;
        margin-bottom: 6px;
    }

    .ops-summary .ops-hero__title {
        font-size: 1.65rem;
        font-weight: 700;
    }

    .ops-summary .ops-hero__subtitle {
        opacity: 0.9;
        font-size: 0.92rem;
    }

    .ops-summary .ops-hero__date {
        font-size: 0.88rem;
        opacity: 0.9;
    }

    .ops-summary .ops-panel {
        background: var(--ops-surface);
        border: 1px solid var(--ops-border);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .ops-summary .ops-panel__header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--ops-border);
        background: linear-gradient(180deg, #fafbfe 0%, #fff 100%);
    }

    .ops-summary .ops-panel__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--ops-ink);
    }

    .ops-summary .ops-panel__subtitle {
        font-size: 0.82rem;
        color: var(--ops-muted);
        margin-top: 2px;
    }

    .ops-summary .ops-panel__body {
        padding: 18px 20px;
    }

    .ops-summary .ops-panel__body--chart {
        min-height: 260px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ops-summary .ops-chart-wrap {
        position: relative;
        height: 280px;
        width: 100%;
    }

    .ops-summary .ops-chart-wrap canvas {
        max-height: 280px;
    }

    .ops-summary .ops-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--ops-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .ops-summary .ops-kpi {
        display: flex;
        align-items: center;
        gap: 14px;
        background: var(--ops-surface);
        border: 1px solid var(--ops-border);
        border-radius: 14px;
        padding: 18px;
        height: 100%;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .ops-summary .ops-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }

    .ops-summary .ops-kpi__icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .ops-summary .ops-kpi--violet .ops-kpi__icon { background: rgba(91, 74, 154, 0.12); color: var(--ops-violet); }
    .ops-summary .ops-kpi--indigo .ops-kpi__icon { background: rgba(91, 74, 154, 0.12); color: var(--ops-violet); }
    .ops-summary .ops-kpi--cyan .ops-kpi__icon { background: rgba(25, 174, 217, 0.12); color: var(--ops-cyan); }
    .ops-summary .ops-kpi--amber .ops-kpi__icon { background: rgba(245, 158, 11, 0.12); color: var(--ops-amber); }
    .ops-summary .ops-kpi--emerald .ops-kpi__icon { background: rgba(16, 185, 129, 0.12); color: var(--ops-emerald); }
    .ops-summary .ops-kpi--rose .ops-kpi__icon { background: rgba(244, 63, 94, 0.12); color: #f43f5e; }

    .ops-summary .ops-kpi__label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--ops-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .ops-summary .ops-kpi__value {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--ops-ink);
        line-height: 1.2;
    }

    .ops-summary .ops-kpi__meta {
        font-size: 0.8rem;
        color: var(--ops-muted);
    }

    .ops-summary .ops-tree-item {
        border: 1px solid var(--ops-border);
        border-radius: 12px;
        margin-bottom: 12px;
        overflow: hidden;
        background: #fff;
    }

    .ops-summary .ops-tree-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .ops-summary .ops-tree-row:hover { background: rgba(91, 74, 154, 0.06); }

    .ops-summary .ops-tree-row--company {
        background: rgba(91, 74, 154, 0.08);
        font-weight: 600;
    }

    .ops-summary .ops-tree-row--client { padding-left: 28px; font-weight: 500; }
    .ops-summary .ops-tree-row--project { padding-left: 44px; font-size: 0.92rem; }

    .ops-summary .ops-tree-chevron {
        font-size: 0.72rem;
        color: var(--ops-muted);
        transition: transform 0.2s ease;
        flex-shrink: 0;
    }

    .ops-summary .ops-tree-row[aria-expanded="true"] .ops-tree-chevron { transform: rotate(90deg); }

    .ops-summary .ops-tree-meta {
        margin-left: auto;
        font-size: 0.8rem;
        color: var(--ops-muted);
        white-space: nowrap;
    }

    .ops-summary .ops-tree-badge {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 3px 8px;
        border-radius: 999px;
    }

    .ops-summary .ops-tree-badge--company { background: rgba(91, 74, 154, 0.15); color: var(--ops-violet); }
    .ops-summary .ops-tree-badge--client { background: rgba(14, 165, 233, 0.12); color: #0284c7; }
    .ops-summary .ops-tree-badge--project { background: rgba(245, 158, 11, 0.12); color: #d97706; }

    .ops-summary .ops-tree-nested { border-top: 1px solid var(--ops-border); }

    .ops-summary .ops-employee-list {
        background: #f8fafc;
        padding: 8px 16px 12px 60px;
    }

    .ops-summary .ops-employee-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px dashed rgba(148, 163, 184, 0.35);
        font-size: 0.86rem;
    }

    .ops-summary .ops-employee-item:last-child { border-bottom: none; }

    .ops-summary .ops-employee-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(16, 185, 129, 0.15);
        color: #059669;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .ops-summary .ops-employee-name { font-weight: 600; color: var(--ops-ink); }
    .ops-summary .ops-employee-meta { font-size: 0.78rem; color: var(--ops-muted); }

    .ops-summary .ops-table thead th {
        background: #f8fafc;
        border-bottom: 2px solid var(--ops-border);
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ops-muted);
    }

    .ops-summary .pagination .page-link {
        color: var(--ops-indigo);
        border-color: var(--ops-border);
    }

    .ops-summary .pagination .page-item.active .page-link {
        background: var(--ops-indigo);
        border-color: var(--ops-indigo);
    }

    @media print {
        .ops-no-print,
        #summary_loading,
        .dataTables_filter,
        .dataTables_length,
        .dt-buttons,
        .dataTables_paginate {
            display: none !important;
        }

        .ops-summary .ops-hero {
            background: #37205b !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .ops-summary .ops-panel,
        .ops-summary .ops-kpi,
        .ops-summary .ops-tree-item {
            break-inside: avoid;
            box-shadow: none;
        }

        #summary_content {
            display: block !important;
        }
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript">
    (function ($) {
        'use strict';

        const summaryUrl = @json(route('report.summary-dashboard'));
        const summaryPdfUrl = @json(route('report.summary-dashboard.pdf'));
        const summaryCsvUrl = @json(route('report.summary-dashboard.csv'));
        const statusLabels = {
            in_progress: @json(__('In Progress')),
            not_started: @json(__('Not Started')),
            completed: @json(__('Completed')),
            deferred: @json(__('Deferred')),
        };
        const statusColors = {
            in_progress: '#37205B',
            not_started: '#BF8CFF',
            completed: '#19AED9',
            deferred: '#F4605B',
        };
        const pipelineColors = ['#37205B', '#5b4a9a', '#BF8CFF', '#19AED9'];
        const chartFont = "'Segoe UI', system-ui, -apple-system, sans-serif";
        const gridColor = 'rgba(148, 163, 184, 0.25)';

        let projectStatusChart = null;
        let pipelineChart = null;
        let deploymentCompanyChart = null;
        let deploymentDataTable = null;
        let companyHierarchyData = [];
        let companyHierarchyPage = 1;
        const companyHierarchyPerPage = 3;

        if (typeof Chart !== 'undefined') {
            Chart.defaults.global.defaultFontFamily = chartFont;
            Chart.defaults.global.defaultFontColor = '#64748b';
        }

        function chartTooltips() {
            return {
                backgroundColor: '#1e293b',
                titleFontSize: 13,
                bodyFontSize: 12,
                cornerRadius: 8,
                xPadding: 12,
                yPadding: 10,
            };
        }

        function destroyCharts() {
            [projectStatusChart, pipelineChart, deploymentCompanyChart].forEach(function (chart) {
                if (chart) {
                    chart.destroy();
                }
            });
            projectStatusChart = null;
            pipelineChart = null;
            deploymentCompanyChart = null;
        }

        function currentFilterParams() {
            return {
                company_id: $('#filter_company_id').val() || '',
                client_id: $('#filter_client_id').val() || '',
                department_id: $('#filter_department_id').val() || '',
                location_id: $('#filter_location_id').val() || '',
                project_status: $('#filter_project_status').val() || '',
            };
        }

        function buildExportQuery() {
            return $.param(currentFilterParams());
        }

        function fillSelect($select, items, valueKey, labelKey) {
            const current = $select.val();
            $select.find('option:not(:first)').remove();

            (items || []).forEach(function (item) {
                $select.append($('<option>', { value: item[valueKey], text: item[labelKey] }));
            });

            $select.selectpicker('refresh');
            if (current) {
                $select.selectpicker('val', current);
            }
        }

        function renderStatCards(totals) {
            const cards = [
                { label: @json(trans('file.Company')), value: totals.companies, sub: @json(__('organizations')), tone: 'indigo', icon: 'dripicons-store' },
                { label: @json(trans('file.Client')), value: totals.clients, sub: totals.active_clients + ' ' + @json(__('active')), tone: 'cyan', icon: 'dripicons-briefcase' },
                { label: @json(trans('file.Projects')), value: totals.projects, sub: totals.running_projects + ' ' + @json(__('running')), tone: 'amber', icon: 'dripicons-checklist' },
                { label: @json(__('Deployed Employees')), value: totals.deployed_resources, sub: @json(__('on projects')), tone: 'emerald', icon: 'dripicons-user-group' },
            ];

            const html = cards.map(function (card) {
                return `
                    <div class="col-sm-6 col-lg-3 mb-3">
                        <div class="ops-kpi ops-kpi--${card.tone}">
                            <div class="ops-kpi__icon"><i class="${card.icon}"></i></div>
                            <div>
                                <div class="ops-kpi__label">${card.label}</div>
                                <div class="ops-kpi__value">${card.value}</div>
                                <div class="ops-kpi__meta">${card.sub}</div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            $('#summary_stat_cards').html(html);
        }

        function renderProjectStatusChart(statusTotals) {
            const labels = [];
            const values = [];
            const colors = [];

            Object.keys(statusTotals).forEach(function (key) {
                if (statusTotals[key] > 0) {
                    labels.push(statusLabels[key] || key);
                    values.push(statusTotals[key]);
                    colors.push(statusColors[key] || '#94a3b8');
                }
            });

            const ctx = document.getElementById('summary_project_status_chart').getContext('2d');
            projectStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverBorderWidth: 3,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutoutPercentage: 68,
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16, usePointStyle: true, fontSize: 11 },
                    },
                    tooltips: chartTooltips(),
                    animation: { animateRotate: true, animateScale: true, duration: 900 },
                },
            });
        }

        function renderPipelineChart(pipeline) {
            const labels = pipeline.labels || [];
            const values = pipeline.values || [];

            const ctx = document.getElementById('summary_pipeline_chart').getContext('2d');
            pipelineChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: @json(__('Count')),
                        data: values,
                        backgroundColor: pipelineColors,
                        borderColor: pipelineColors,
                        borderWidth: 0,
                        maxBarThickness: 48,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: false },
                    tooltips: chartTooltips(),
                    scales: {
                        xAxes: [{
                            gridLines: { display: false },
                            ticks: { fontSize: 10, maxRotation: 25, minRotation: 0 },
                        }],
                        yAxes: [{
                            ticks: { beginAtZero: true, precision: 0, fontSize: 10 },
                            gridLines: { color: gridColor, drawBorder: false },
                        }],
                    },
                    animation: { duration: 900 },
                },
            });
        }

        function renderDeploymentCompanyChart(chartData) {
            const items = (chartData || []).slice(0, 8);
            const labels = items.map(function (item) { return item.name; });
            const deployed = items.map(function (item) { return item.deployed_count; });
            const projects = items.map(function (item) { return item.projects_count; });
            const barColors = ['#10b981', '#34d399', '#6ee7b7', '#059669', '#047857', '#065f46', '#064e3b', '#022c22'];

            const ctx = document.getElementById('summary_deployment_company_chart').getContext('2d');
            deploymentCompanyChart = new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: @json(__('Employees')),
                            backgroundColor: barColors.slice(0, labels.length),
                            data: deployed,
                        },
                        {
                            label: @json(trans('file.Projects')),
                            backgroundColor: 'rgba(91, 74, 154, 0.35)',
                            data: projects,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'bottom', labels: { usePointStyle: true, fontSize: 11 } },
                    tooltips: chartTooltips(),
                    scales: {
                        xAxes: [{
                            stacked: false,
                            ticks: { beginAtZero: true, precision: 0, fontSize: 10 },
                            gridLines: { color: gridColor, drawBorder: false },
                        }],
                        yAxes: [{
                            stacked: false,
                            gridLines: { display: false },
                            ticks: { fontSize: 10 },
                        }],
                    },
                    animation: { duration: 900 },
                },
            });
        }

        function buildEmployeeListHtml(employees) {
            if (!employees || !employees.length) {
                return '<div class="ops-employee-list"><p class="text-muted mb-0 small py-2">' + @json(__('No deployed employees')) + '</p></div>';
            }

            const rows = employees.map(function (employee) {
                return `
                    <div class="ops-employee-item">
                        <div class="ops-employee-avatar"><i class="dripicons-user"></i></div>
                        <div>
                            <div class="ops-employee-name">${employee.name}</div>
                            <div class="ops-employee-meta">
                                ${employee.staff_id} · ${employee.designation} · ${employee.department} · ${employee.location}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            return '<div class="ops-employee-list">' + rows + '</div>';
        }

        function buildCompanyCardHtml(company) {
            const clientBlocks = (company.clients || []).map(function (client) {
                const projectBlocks = (client.projects || []).map(function (project) {
                    const employeeSection = buildEmployeeListHtml(project.employees);

                    return `
                        <div class="ops-tree-nested">
                            <div class="ops-tree-row ops-tree-row--project"
                                 data-toggle="collapse"
                                 data-target="#project_sub_${company.id}_${client.id}_${project.id}"
                                 aria-expanded="false">
                                <i class="dripicons-chevron-right ops-tree-chevron"></i>
                                <span>${project.title}</span>
                                <span class="ops-tree-badge ops-tree-badge--project ml-2">${@json(trans('file.Project'))}</span>
                                <span class="badge badge-light ml-2">${project.status_label}</span>
                                <span class="ops-tree-meta">${project.deployed_count || 0} ${@json(__('employees'))}</span>
                            </div>
                            <div id="project_sub_${company.id}_${client.id}_${project.id}" class="collapse">
                                ${employeeSection}
                            </div>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="ops-tree-nested">
                        <div class="ops-tree-row ops-tree-row--client"
                             data-toggle="collapse"
                             data-target="#client_sub_${company.id}_${client.id}"
                             aria-expanded="false">
                            <i class="dripicons-chevron-right ops-tree-chevron"></i>
                            <span>${client.name}</span>
                            <span class="ops-tree-badge ops-tree-badge--client ml-2">${@json(trans('file.Client'))}</span>
                            <span class="ops-tree-meta">
                                ${client.projects_count} ${@json(trans('file.Projects'))} · ${client.deployed_resources || 0} ${@json(__('employees'))}
                            </span>
                        </div>
                        <div id="client_sub_${company.id}_${client.id}" class="collapse">
                            ${projectBlocks || '<p class="text-muted mb-0 px-4 py-2 small">' + @json(__('No projects')) + '</p>'}
                        </div>
                    </div>
                `;
            }).join('');

            return `
                <div class="ops-tree-item">
                    <div class="ops-tree-row ops-tree-row--company"
                         data-toggle="collapse"
                         data-target="#company_panel_${company.id}"
                         aria-expanded="false">
                        <i class="dripicons-chevron-right ops-tree-chevron"></i>
                        <span>${company.name}</span>
                        <span class="ops-tree-badge ops-tree-badge--company ml-2">${@json(trans('file.Company'))}</span>
                        <span class="ops-tree-meta">
                            ${company.clients_count} ${@json(trans('file.Client'))} ·
                            ${company.total_projects} ${@json(trans('file.Projects'))} ·
                            ${company.deployed_resources || 0} ${@json(__('employees'))}
                        </span>
                    </div>
                    <div id="company_panel_${company.id}" class="collapse">
                        ${clientBlocks || '<p class="text-muted mb-0 px-4 py-3 small">' + @json(__('No linked clients')) + '</p>'}
                    </div>
                </div>
            `;
        }

        function renderCompanyHierarchyPagination(totalPages) {
            const $pagination = $('#company_breakdown_pagination');

            if (totalPages <= 1) {
                $pagination.empty();
                return;
            }

            let html = '<nav aria-label="Company hierarchy pages" class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0">';
            html += '<li class="page-item' + (companyHierarchyPage === 1 ? ' disabled' : '') + '">';
            html += '<a class="page-link" href="#" data-company-page="' + (companyHierarchyPage - 1) + '">' + @json(__('Previous')) + '</a></li>';

            for (let page = 1; page <= totalPages; page++) {
                html += '<li class="page-item' + (page === companyHierarchyPage ? ' active' : '') + '">';
                html += '<a class="page-link" href="#" data-company-page="' + page + '">' + page + '</a></li>';
            }

            html += '<li class="page-item' + (companyHierarchyPage === totalPages ? ' disabled' : '') + '">';
            html += '<a class="page-link" href="#" data-company-page="' + (companyHierarchyPage + 1) + '">' + @json(__('Next')) + '</a></li>';
            html += '</ul></nav>';

            $pagination.html(html);
        }

        function showCompanyHierarchyPage(page) {
            const total = companyHierarchyData.length;
            const totalPages = Math.max(1, Math.ceil(total / companyHierarchyPerPage));

            companyHierarchyPage = Math.min(Math.max(1, page), totalPages);
            const start = (companyHierarchyPage - 1) * companyHierarchyPerPage;
            const slice = companyHierarchyData.slice(start, start + companyHierarchyPerPage);

            $('#company_breakdown').html(slice.map(buildCompanyCardHtml).join(''));
            renderCompanyHierarchyPagination(totalPages);
        }

        function renderCompanyBreakdown(companies) {
            companyHierarchyData = companies || [];
            companyHierarchyPage = 1;

            if (!companyHierarchyData.length) {
                $('#company_breakdown').html('<p class="text-muted mb-0">' + @json(__('No company data found for the selected filters.')) + '</p>');
                $('#company_breakdown_pagination').empty();
                return;
            }

            showCompanyHierarchyPage(1);
        }

        $(document).on('click', '#company_breakdown_pagination .page-link', function (event) {
            event.preventDefault();
            const $item = $(this).parent();
            if ($item.hasClass('disabled') || $item.hasClass('active')) {
                return;
            }
            const page = parseInt($(this).data('company-page'), 10);
            if (!isNaN(page)) {
                showCompanyHierarchyPage(page);
            }
        });

        function renderDeploymentTable(deployments) {
            const rows = [];

            (deployments || []).forEach(function (deployment) {
                const projects = deployment.projects || [];

                if (!projects.length) {
                    rows.push([
                        deployment.employee_name,
                        deployment.staff_id,
                        deployment.company,
                        deployment.client,
                        deployment.location,
                        deployment.department,
                        deployment.designation,
                        '---',
                    ]);
                    return;
                }

                projects.forEach(function (project, index) {
                    rows.push([
                        index === 0 ? deployment.employee_name : '',
                        index === 0 ? deployment.staff_id : '',
                        project.company || (index === 0 ? deployment.company : ''),
                        index === 0 ? deployment.client : '',
                        index === 0 ? deployment.location : '',
                        project.department || deployment.department,
                        index === 0 ? deployment.designation : '',
                        project.title + ' (' + (statusLabels[project.status] || project.status) + ')',
                    ]);
                });
            });

            if (deploymentDataTable) {
                deploymentDataTable.clear();
                deploymentDataTable.rows.add(rows);
                deploymentDataTable.draw();
                return;
            }

            deploymentDataTable = $('#deployment_table').DataTable({
                data: rows,
                columns: [
                    { title: @json(trans('file.Employee')) },
                    { title: @json(__('Staff ID')) },
                    { title: @json(trans('file.Company')) },
                    { title: @json(trans('file.Client')) },
                    { title: @json(trans('file.Location')) },
                    { title: @json(trans('file.Department')) },
                    { title: @json(trans('file.Designation')) },
                    { title: @json(trans('file.Project')) },
                ],
                order: [[0, 'asc']],
                pageLength: 25,
                dom: '<"row ops-no-print"lfB>rtip',
                buttons: [
                    {
                        extend: 'pdf',
                        text: '<i class="fa fa-file-pdf-o"></i> PDF',
                        title: @json(__('Resource Deployment Matrix')),
                        exportOptions: { columns: ':visible', rows: ':visible' },
                    },
                    {
                        extend: 'csv',
                        text: '<i class="fa fa-file-text-o"></i> CSV',
                        exportOptions: { columns: ':visible', rows: ':visible' },
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> ' + @json(__('Print')),
                        exportOptions: { columns: ':visible', rows: ':visible' },
                    },
                ],
            });
        }

        function renderDashboard(data) {
            if (typeof Chart === 'undefined') {
                alert(@json(__('Charts library is not loaded.')));
                return;
            }

            destroyCharts();
            renderStatCards(data.totals || {});
            renderProjectStatusChart(data.project_status_totals || {});
            renderPipelineChart((data.charts && data.charts.pipeline) || { labels: [], values: [] });
            renderDeploymentCompanyChart((data.charts && data.charts.deployment_by_company) || []);
            renderCompanyBreakdown(data.companies || []);
            renderDeploymentTable(data.deployments || []);

            fillSelect($('#filter_company_id'), data.filters.companies, 'id', 'name');
            fillSelect($('#filter_client_id'), data.filters.clients, 'id', 'name');
            fillSelect($('#filter_department_id'), data.filters.departments, 'id', 'name');
            fillSelect($('#filter_location_id'), data.filters.locations, 'id', 'name');

            const $statusSelect = $('#filter_project_status');
            const statusCurrent = $statusSelect.val();
            $statusSelect.find('option:not(:first)').remove();
            (data.filters.statuses || []).forEach(function (status) {
                $statusSelect.append($('<option>', { value: status.value, text: status.label }));
            });
            $statusSelect.selectpicker('refresh');
            if (statusCurrent) {
                $statusSelect.selectpicker('val', statusCurrent);
            }

            $('#summary_loading').hide();
            $('#summary_content').show();
        }

        function loadSummaryDashboard() {
            $('#summary_loading').show();
            $('#summary_content').hide();

            $.ajax({
                url: summaryUrl,
                type: 'GET',
                dataType: 'json',
                data: currentFilterParams(),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    renderDashboard(response);
                },
                error: function (xhr) {
                    $('#summary_loading').hide();
                    const message = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : @json(__('Unable to load summary dashboard.'));
                    alert(message);
                },
            });
        }

        $('#export_summary_pdf').on('click', function (event) {
            event.preventDefault();
            window.location.href = summaryPdfUrl + '?' + buildExportQuery();
        });

        $('#export_summary_csv').on('click', function (event) {
            event.preventDefault();
            window.location.href = summaryCsvUrl + '?' + buildExportQuery();
        });

        $('#print_summary_dashboard').on('click', function () {
            window.print();
        });

        $('#summary_filter_form').on('submit', function (event) {
            event.preventDefault();
            loadSummaryDashboard();
        });

        $('#reset_summary_filters').on('click', function () {
            $('#filter_company_id, #filter_client_id, #filter_department_id, #filter_location_id, #filter_project_status').selectpicker('val', '');
            loadSummaryDashboard();
        });

        function resetSummarySelect($select) {
            $select.selectpicker('destroy');
            $select.html('<option value="">' + @json(__('All')) + '</option>');
            $select.selectpicker();
        }

        function loadSummaryClients(companyId) {
            resetSummarySelect($('#filter_client_id'));

            if (!companyId) {
                return $.Deferred().resolve().promise();
            }

            return $.post("{{ route('dynamic_clients') }}", {
                value: companyId,
                _token: '{{ csrf_token() }}'
            }).done(function(result) {
                $('#filter_client_id').selectpicker('destroy');
                $('#filter_client_id').html('<option value="">' + @json(__('All')) + '</option>' + result);
                $('#filter_client_id').selectpicker();
            });
        }

        function loadSummaryLocations(companyId, clientId) {
            resetSummarySelect($('#filter_location_id'));

            if (!companyId && !clientId) {
                return $.Deferred().resolve().promise();
            }

            return $.post("{{ route('dynamic_locations') }}", {
                company_id: companyId || '',
                client_id: clientId || '',
                _token: '{{ csrf_token() }}'
            }).done(function(result) {
                $('#filter_location_id').selectpicker('destroy');
                $('#filter_location_id').html('<option value="">' + @json(__('All')) + '</option>' + result);
                $('#filter_location_id').selectpicker();
            });
        }

        $('#filter_company_id').on('changed.bs.select', function () {
            var companyId = $(this).val();
            loadSummaryClients(companyId).always(function () {
                loadSummaryLocations(companyId, '');
            });
        });

        $('#filter_client_id').on('changed.bs.select', function () {
            loadSummaryLocations($('#filter_company_id').val(), $(this).val());
        });

        $('#refresh_summary_dashboard').on('click', function () {
            loadSummaryDashboard();
        });

        $(document).ready(function () {
            loadSummaryDashboard();
        });
    })(jQuery);
</script>
@endpush
