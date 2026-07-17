@extends('layout.main')
@section('content')

@php
    $status = $dashboard['charts']['project_status'] ?? [];
    $statusTotal = array_sum($status);
    $statusBreakdown = $dashboard['charts']['status_breakdown'] ?? [];
    $departmentHeadcount = $dashboard['charts']['department_headcount'] ?? collect();
    $initials = $dashboard['initials'] ?? 'HR';
@endphp

<section class="entity-dash">
    <div class="container-fluid">
        {{-- Hero --}}
        <div class="entity-hero mb-4">
            <div class="entity-hero__topbar">
                <nav class="entity-breadcrumb mb-0">
                    <a href="{{ $dashboard['back_url'] }}">{{ $dashboard['back_label'] }}</a>
                    <span>/</span>
                    <span>{{ __('Dashboard') }}</span>
                </nav>
                <div class="entity-hero__topbar-date text-right">
                    <div class="entity-hero__date">{{ now()->englishDayOfWeek }}</div>
                    <div class="entity-hero__date-sub">{{ now()->format(env('Date_Format')) }}</div>
                </div>
            </div>
            <div class="entity-hero__content">
                <div class="d-flex flex-wrap align-items-start justify-content-between">
                    <div class="d-flex align-items-start entity-hero__identity">
                        <div class="entity-hero__avatar" id="entity_avatar_wrap">
                            @if (! empty($dashboard['logo_url']))
                                <img src="{{ $dashboard['logo_url'] }}" alt="{{ $dashboard['title'] }}"
                                     onerror="this.style.display='none'; document.getElementById('entity_avatar_initials').style.display='flex';">
                                <span id="entity_avatar_initials" style="display:none;">{{ strtoupper($initials) }}</span>
                            @else
                                <span>{{ strtoupper($initials) }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="entity-hero__badges mb-2">
                                <span class="entity-type-badge entity-type-badge--{{ $dashboard['type'] }}">{{ $dashboard['type_label'] ?? ucfirst($dashboard['type']) }}</span>
                                <span class="entity-id-badge">#{{ $dashboard['entity_id'] ?? '—' }}</span>
                            </div>
                            <h1 class="entity-hero__title">{{ $dashboard['title'] }}</h1>
                            <p class="entity-hero__subtitle mb-0">{{ $dashboard['subtitle'] }}</p>
                            @if (! empty($dashboard['company_dashboard_url']))
                                <a href="{{ $dashboard['company_dashboard_url'] }}" class="btn btn-sm entity-hero__link-btn mt-2">
                                    <i class="dripicons-store"></i> {{ __('View Company Dashboard') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @if (! empty($dashboard['meta']))
                    <div class="entity-hero__meta mt-3">
                        @foreach ($dashboard['meta'] as $label => $value)
                            <span class="entity-meta-chip">
                                <i class="dripicons-information"></i>
                                <strong>{{ $label }}:</strong> {{ $value }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Featured revenue + quick links --}}
        <div class="row mb-4">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="entity-feature h-100">
                    <div class="entity-feature__label">{{ $dashboard['featured']['label'] ?? __('Total Revenue') }}</div>
                    <div class="entity-feature__value">{{ $dashboard['featured']['value'] ?? '—' }}</div>
                    <div class="entity-feature__meta">{{ $dashboard['featured']['meta'] ?? '' }}</div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="entity-quick h-100">
                    <div class="entity-quick__title">{{ __('Quick Actions') }}</div>
                    <div class="entity-quick__grid">
                        @foreach ($dashboard['quick_links'] ?? [] as $link)
                            <a href="{{ $link['url'] }}" class="entity-quick__item">
                                <i class="{{ $link['icon'] }}"></i>
                                <span>{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Highlight pills --}}
        @if (! empty($dashboard['highlights']))
            <div class="row mb-4">
                @foreach ($dashboard['highlights'] as $pill)
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="entity-pill entity-pill--{{ $pill['tone'] }}">
                            <div class="entity-pill__icon"><i class="{{ $pill['icon'] }}"></i></div>
                            <div>
                                <div class="entity-pill__label">{{ $pill['label'] }}</div>
                                <div class="entity-pill__value">{{ $pill['value'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- KPI cards --}}
        <div class="row mb-4">
            @foreach ($dashboard['kpis'] as $card)
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    @if (! empty($card['url']))
                        <a href="{{ $card['url'] }}" class="entity-kpi entity-kpi--{{ $card['tone'] }} entity-kpi--clickable">
                            <div class="entity-kpi__icon"><i class="{{ $card['icon'] }}"></i></div>
                            <div class="entity-kpi__body">
                                <div class="entity-kpi__label">{{ $card['label'] }}</div>
                                <div class="entity-kpi__value">{{ is_numeric($card['value']) ? number_format((int) $card['value']) : $card['value'] }}</div>
                                @if (! empty($card['hint']))
                                    <div class="entity-kpi__hint">{{ $card['hint'] }}</div>
                                @endif
                                <div class="entity-kpi__cta">{{ __('View details') }} <i class="dripicons-chevron-right"></i></div>
                            </div>
                        </a>
                    @else
                        <div class="entity-kpi entity-kpi--{{ $card['tone'] }}">
                            <div class="entity-kpi__icon"><i class="{{ $card['icon'] }}"></i></div>
                            <div class="entity-kpi__body">
                                <div class="entity-kpi__label">{{ $card['label'] }}</div>
                                <div class="entity-kpi__value">{{ is_numeric($card['value']) ? number_format((int) $card['value']) : $card['value'] }}</div>
                                @if (! empty($card['hint']))
                                    <div class="entity-kpi__hint">{{ $card['hint'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if (($dashboard['type'] ?? '') === 'client' && ! empty($dashboard['kpi_details']))
            @php $details = $dashboard['kpi_details']; @endphp
            <div id="entity-kpi-details-wrap" class="mb-4">

            <div id="entity-detail-projects" class="entity-panel entity-detail-panel mb-0 d-none" data-page-size="5">
                <div class="entity-panel__head entity-panel__head--row">
                    <div>
                        <h3>{{ trans('file.Projects') }}</h3>
                        <p>{{ __('All projects for this client') }}</p>
                    </div>
                    <div class="d-flex align-items-center entity-detail-actions">
                        <a href="{{ route('projects.index') }}" class="btn btn-sm entity-btn-outline">{{ __('Open Projects') }}</a>
                        <button type="button" class="btn btn-sm entity-btn-ghost entity-detail-close" aria-label="{{ __('Close') }}">&times;</button>
                    </div>
                </div>
                <div class="entity-panel__body p-0">
                    @if (($details['projects'] ?? collect())->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table entity-table mb-0 entity-paged-table">
                                <thead>
                                <tr>
                                    <th>{{ trans('file.Project') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ trans('file.Status') }}</th>
                                    <th>{{ __('Progress') }}</th>
                                    <th>{{ __('Revenue') }}</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($details['projects'] as $project)
                                    <tr class="entity-page-row">
                                        <td><a href="{{ $project['url'] }}" class="entity-table__title">{{ $project['title'] }}</a></td>
                                        <td>{{ $project['category'] ?? '—' }}</td>
                                        <td><span class="entity-status {{ $project['status_class'] }}">{{ $project['status_label'] }}</span></td>
                                        <td style="min-width:140px">
                                            <div class="entity-progress">
                                                <div class="entity-progress__bar" style="width:{{ $project['progress'] }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $project['progress'] }}%</small>
                                        </td>
                                        <td class="entity-table__money">{{ $project['revenue'] }}</td>
                                        <td class="text-right">
                                            <a href="{{ $project['url'] }}" class="btn btn-sm entity-btn-ghost">{{ __('Open') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="entity-pagination"></div>
                    @else
                        <div class="entity-empty"><i class="dripicons-checklist"></i><p>{{ __('No projects found yet.') }}</p></div>
                    @endif
                </div>
            </div>

            <div id="entity-detail-categories" class="entity-panel entity-detail-panel mb-0 d-none" data-page-size="5">
                <div class="entity-panel__head entity-panel__head--row">
                    <div>
                        <h3>{{ __('Project Categories') }}</h3>
                        <p>{{ __('Service lines used by this client') }}</p>
                    </div>
                    <div class="d-flex align-items-center entity-detail-actions">
                        <a href="{{ route('project_categories.index') }}" class="btn btn-sm entity-btn-outline">{{ __('Open Categories') }}</a>
                        <button type="button" class="btn btn-sm entity-btn-ghost entity-detail-close" aria-label="{{ __('Close') }}">&times;</button>
                    </div>
                </div>
                <div class="entity-panel__body p-0">
                    @if (($details['categories'] ?? collect())->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table entity-table mb-0 entity-paged-table">
                                <thead>
                                <tr>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Projects') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($details['categories'] as $category)
                                    <tr class="entity-page-row">
                                        <td class="entity-table__title">{{ $category['name'] }}</td>
                                        <td>{{ $category['description'] ?: '—' }}</td>
                                        <td>{{ $category['project_count'] }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="entity-pagination"></div>
                    @else
                        <div class="entity-empty"><i class="dripicons-folder"></i><p>{{ __('No project categories found.') }}</p></div>
                    @endif
                </div>
            </div>

            <div id="entity-detail-employees" class="entity-panel entity-detail-panel mb-0 d-none" data-page-size="5">
                <div class="entity-panel__head entity-panel__head--row">
                    <div>
                        <h3>{{ trans('file.Employees') }}</h3>
                        <p>{{ __('Active employees belonging to this client') }}</p>
                    </div>
                    <div class="d-flex align-items-center entity-detail-actions">
                        <a href="{{ route('employees.index', ['client_id' => $dashboard['entity_id']]) }}" class="btn btn-sm entity-btn-outline">{{ __('Open Employees') }}</a>
                        <button type="button" class="btn btn-sm entity-btn-ghost entity-detail-close" aria-label="{{ __('Close') }}">&times;</button>
                    </div>
                </div>
                <div class="entity-panel__body p-0">
                    @if (($details['employees'] ?? collect())->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table entity-table mb-0 entity-paged-table">
                                <thead>
                                <tr>
                                    <th>{{ trans('file.Employee') }}</th>
                                    <th>{{ __('Staff Id') }}</th>
                                    <th>{{ trans('file.Department') }}</th>
                                    <th>{{ trans('file.Designation') }}</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($details['employees'] as $employee)
                                    <tr class="entity-page-row">
                                        <td><a href="{{ $employee['url'] }}" class="entity-table__title">{{ $employee['name'] }}</a></td>
                                        <td>{{ $employee['staff_id'] }}</td>
                                        <td>{{ $employee['department'] }}</td>
                                        <td>{{ $employee['designation'] }}</td>
                                        <td class="text-right"><a href="{{ $employee['url'] }}" class="btn btn-sm entity-btn-ghost">{{ __('Open') }}</a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="entity-pagination"></div>
                    @else
                        <div class="entity-empty"><i class="dripicons-user-id"></i><p>{{ __('No client employees found.') }}</p></div>
                    @endif
                </div>
            </div>

            <div id="entity-detail-assigned" class="entity-panel entity-detail-panel mb-0 d-none" data-page-size="5">
                <div class="entity-panel__head entity-panel__head--row">
                    <div>
                        <h3>{{ __('Assigned Employees') }}</h3>
                        <p>{{ __('Team members assigned on this client’s projects') }}</p>
                    </div>
                    <div class="d-flex align-items-center entity-detail-actions">
                        <button type="button" class="btn btn-sm entity-btn-ghost entity-detail-close" aria-label="{{ __('Close') }}">&times;</button>
                    </div>
                </div>
                <div class="entity-panel__body p-0">
                    @if (($details['assigned'] ?? collect())->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table entity-table mb-0 entity-paged-table">
                                <thead>
                                <tr>
                                    <th>{{ trans('file.Employee') }}</th>
                                    <th>{{ __('Staff Id') }}</th>
                                    <th>{{ trans('file.Department') }}</th>
                                    <th>{{ __('Assigned Projects') }}</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($details['assigned'] as $employee)
                                    <tr class="entity-page-row">
                                        <td><a href="{{ $employee['url'] }}" class="entity-table__title">{{ $employee['name'] }}</a></td>
                                        <td>{{ $employee['staff_id'] }}</td>
                                        <td>{{ $employee['department'] }}</td>
                                        <td>{{ $employee['projects'] ?? '—' }}</td>
                                        <td class="text-right"><a href="{{ $employee['url'] }}" class="btn btn-sm entity-btn-ghost">{{ __('Open') }}</a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="entity-pagination"></div>
                    @else
                        <div class="entity-empty"><i class="dripicons-user-group"></i><p>{{ __('No assigned employees found.') }}</p></div>
                    @endif
                </div>
            </div>

            <div id="entity-detail-invoices" class="entity-panel entity-detail-panel mb-0 d-none" data-page-size="5">
                <div class="entity-panel__head entity-panel__head--row">
                    <div>
                        <h3>{{ trans('file.Invoice') }}</h3>
                        <p>{{ __('Billing records for this client') }}</p>
                    </div>
                    <div class="d-flex align-items-center entity-detail-actions">
                        <a href="{{ route('invoices.index') }}" class="btn btn-sm entity-btn-outline">{{ __('Open Invoices') }}</a>
                        <button type="button" class="btn btn-sm entity-btn-ghost entity-detail-close" aria-label="{{ __('Close') }}">&times;</button>
                    </div>
                </div>
                <div class="entity-panel__body p-0">
                    @if (($details['invoices'] ?? collect())->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table entity-table mb-0 entity-paged-table">
                                <thead>
                                <tr>
                                    <th>{{ __('Invoice #') }}</th>
                                    <th>{{ trans('file.Status') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($details['invoices'] as $invoice)
                                    <tr class="entity-page-row">
                                        <td class="entity-table__title">{{ $invoice['number'] }}</td>
                                        <td>{{ $invoice['status'] }}</td>
                                        <td class="entity-table__money">{{ $invoice['grand_total'] }}</td>
                                        <td class="text-right"><a href="{{ $invoice['url'] }}" class="btn btn-sm entity-btn-ghost">{{ __('Open') }}</a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="entity-pagination"></div>
                    @else
                        <div class="entity-empty"><i class="dripicons-document"></i><p>{{ __('No invoices found.') }}</p></div>
                    @endif
                </div>
            </div>

            </div>
        @endif

        <div class="row">
            {{-- Project status --}}
            <div class="{{ $dashboard['type'] === 'client' ? 'col-lg-12' : 'col-lg-4' }} mb-4">
                <div class="entity-panel h-100">
                    <div class="entity-panel__head">
                        <h3>{{ __('Project Portfolio') }}</h3>
                        <p>{{ __('Status distribution') }}</p>
                    </div>
                    <div class="entity-panel__body">
                        @if ($statusTotal > 0)
                            <div class="entity-chart-ring mb-3">
                                <canvas id="entity_project_status_chart"></canvas>
                                <div class="entity-chart-ring__center">
                                    <strong>{{ $statusTotal }}</strong>
                                    <span>{{ trans('file.Projects') }}</span>
                                </div>
                            </div>
                            <div class="entity-legend">
                                @foreach ($statusBreakdown as $item)
                                    <div class="entity-legend__row">
                                        <span class="entity-legend__dot" style="background:{{ $item['color'] }}"></span>
                                        <span class="entity-legend__label">{{ $item['label'] }}</span>
                                        <span class="entity-legend__count">{{ $item['count'] }}</span>
                                        <span class="entity-legend__pct">{{ $item['percent'] }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="entity-empty">
                                <i class="dripicons-information"></i>
                                <p>{{ __('No projects found yet.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Department chart (company only) --}}
            @if ($dashboard['type'] === 'company')
            <div class="col-lg-8 mb-4">
                @if ($departmentHeadcount->isNotEmpty())
                    <div class="entity-panel h-100 mb-4 mb-lg-0">
                        <div class="entity-panel__head">
                            <h3>{{ __('Workforce by Department') }}</h3>
                            <p>{{ __('Active employees across departments') }}</p>
                        </div>
                        <div class="entity-panel__body entity-panel__body--chart">
                            <canvas id="entity_department_chart"></canvas>
                        </div>
                    </div>
                @elseif (($dashboard['related'] ?? collect())->isNotEmpty())
                    <div class="entity-panel h-100">
                        <div class="entity-panel__head d-flex justify-content-between align-items-start">
                            <div>
                                <h3>{{ $dashboard['related_title'] ?? __('Related') }}</h3>
                                <p>{{ __('Browse linked records') }}</p>
                            </div>
                        </div>
                        <div class="entity-panel__body">
                            <div class="row">
                                @foreach ($dashboard['related'] as $item)
                                    <div class="col-md-6 mb-3">
                                        <a href="{{ $item['url'] }}" class="entity-card-link">
                                            <div class="entity-card-link__avatar">{{ $item['initials'] ?? '—' }}</div>
                                            <div class="entity-card-link__body">
                                                <div class="entity-card-link__name">{{ $item['name'] }}</div>
                                                <div class="entity-card-link__meta">{{ $item['meta'] }}</div>
                                            </div>
                                            <i class="dripicons-chevron-right entity-card-link__arrow"></i>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            @endif
        </div>

        @if ($dashboard['type'] === 'company' && ($dashboard['related'] ?? collect())->isNotEmpty())
            <div class="entity-panel mb-4">
                <div class="entity-panel__head">
                    <h3>{{ $dashboard['related_title'] }}</h3>
                    <p>{{ __('Client accounts under this company') }}</p>
                </div>
                <div class="entity-panel__body">
                    <div class="row">
                        @foreach ($dashboard['related'] as $item)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <a href="{{ $item['url'] }}" class="entity-card-link">
                                    <div class="entity-card-link__avatar">{{ $item['initials'] }}</div>
                                    <div class="entity-card-link__body">
                                        <div class="entity-card-link__name">{{ $item['name'] }}</div>
                                        <div class="entity-card-link__meta">{{ $item['meta'] }}</div>
                                    </div>
                                    <i class="dripicons-chevron-right entity-card-link__arrow"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Recent projects --}}
        <div class="entity-panel mb-4">
            <div class="entity-panel__head entity-panel__head--row">
                <div>
                    <h3>{{ __('Recent Projects') }}</h3>
                    <p>{{ __('Latest activity in this portfolio') }}</p>
                </div>
                <a href="{{ route('projects.index') }}" class="btn btn-sm entity-btn-outline">{{ __('View All') }}</a>
            </div>
            <div class="entity-panel__body p-0">
                @if (($dashboard['recent_projects'] ?? collect())->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table entity-table mb-0">
                            <thead>
                            <tr>
                                <th>{{ trans('file.Project') }}</th>
                                @if ($dashboard['type'] === 'company')
                                    <th>{{ trans('file.Client') }}</th>
                                @endif
                                <th>{{ trans('file.Status') }}</th>
                                <th>{{ __('Progress') }}</th>
                                <th>{{ __('Revenue') }}</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($dashboard['recent_projects'] as $project)
                                <tr>
                                    <td>
                                        <a href="{{ $project['url'] }}" class="entity-table__title">{{ $project['title'] }}</a>
                                    </td>
                                    @if ($dashboard['type'] === 'company')
                                        <td>{{ $project['client'] }}</td>
                                    @endif
                                    <td>
                                        <span class="entity-status {{ $project['status_class'] }}">{{ $project['status_label'] }}</span>
                                    </td>
                                    <td style="min-width:140px">
                                        <div class="entity-progress">
                                            <div class="entity-progress__bar" style="width:{{ $project['progress'] }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $project['progress'] }}%</small>
                                    </td>
                                    <td class="entity-table__money">{{ $project['revenue'] }}</td>
                                    <td class="text-right">
                                        <a href="{{ $project['url'] }}" class="btn btn-sm entity-btn-ghost">{{ __('Open') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="entity-empty">
                        <i class="dripicons-checklist"></i>
                        <p>{{ __('No projects found yet.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection

@push('css')
<style>
    .entity-dash {
        --ed-violet: #37205B;
        --ed-violet-mid: #5b4a9a;
        --ed-cyan: #0ea5e9;
        --ed-amber: #f59e0b;
        --ed-emerald: #10b981;
        --ed-indigo: #4f46e5;
        --ed-ink: #0f172a;
        --ed-muted: #64748b;
        --ed-border: #e2e8f0;
        --ed-surface: #ffffff;
        --ed-bg: #f8fafc;
    }

    .entity-hero {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        background: var(--ed-surface);
        border: 1px solid var(--ed-border);
        box-shadow: 0 12px 40px rgba(55, 32, 91, 0.08);
    }
    .entity-hero__topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 24px;
        background: linear-gradient(135deg, #37205B 0%, #5b4a9a 50%, #7c6bcb 100%);
        color: #fff;
    }
    .entity-hero__topbar-date { flex-shrink: 0; }
    .entity-hero__content {
        position: relative;
        padding: 24px 28px 22px;
    }
    .entity-hero__identity { gap: 20px; max-width: 100%; }
    .entity-hero__avatar {
        width: 88px; height: 88px; border-radius: 20px;
        background: linear-gradient(135deg, #37205B, #5b4a9a);
        border: 4px solid #fff; box-shadow: 0 8px 24px rgba(15,23,42,.12);
        display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;
    }
    .entity-hero__avatar img { width: 100%; height: 100%; object-fit: cover; background: #fff; }
    .entity-hero__avatar span {
        font-size: 1.5rem; font-weight: 800; color: #fff;
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
    }
    .entity-breadcrumb { font-size: 0.82rem; }
    .entity-breadcrumb a { color: #fff; font-weight: 600; text-decoration: none; opacity: .92; }
    .entity-breadcrumb a:hover { opacity: 1; text-decoration: underline; }
    .entity-breadcrumb span { color: rgba(255,255,255,.75); margin: 0 6px; }
    .entity-hero__date { font-size: 1rem; font-weight: 700; color: #fff; line-height: 1.2; }
    .entity-hero__date-sub { font-size: 0.85rem; color: rgba(255,255,255,.82); }
    .entity-hero__link-btn {
        border-radius: 999px; font-weight: 600; color: var(--ed-violet-mid);
        background: rgba(91,74,154,.1); border: 1px solid rgba(91,74,154,.2);
    }
    .entity-hero__link-btn:hover { background: var(--ed-violet-mid); color: #fff; }
    .entity-type-badge {
        display: inline-block; padding: 4px 12px; border-radius: 999px;
        font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
    }
    .entity-type-badge--company { background: rgba(79,70,229,.12); color: #4f46e5; }
    .entity-type-badge--client { background: rgba(14,165,233,.12); color: #0284c7; }
    .entity-id-badge {
        display: inline-block; margin-left: 8px; padding: 4px 10px; border-radius: 999px;
        background: #f1f5f9; color: var(--ed-muted); font-size: 0.72rem; font-weight: 600;
    }
    .entity-hero__title { font-size: 1.85rem; font-weight: 800; color: var(--ed-ink); margin-bottom: 4px; }
    .entity-hero__subtitle { color: var(--ed-muted); font-size: 0.95rem; }
    .entity-hero__meta { display: flex; flex-wrap: wrap; gap: 8px; padding-top: 4px; border-top: 1px solid var(--ed-border); }
    .entity-meta-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
        background: var(--ed-bg); border: 1px solid var(--ed-border); border-radius: 999px;
        font-size: 0.8rem; color: var(--ed-muted);
    }
    .entity-feature {
        background: linear-gradient(135deg, #37205B, #5b4a9a);
        color: #fff; border-radius: 18px; padding: 28px 32px;
        box-shadow: 0 16px 40px rgba(55,32,91,.2);
    }
    .entity-feature__label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; opacity: .85; }
    .entity-feature__value { font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800; line-height: 1.1; margin: 8px 0; word-break: break-word; }
    .entity-feature__meta { font-size: 0.9rem; opacity: .8; }

    .entity-quick {
        background: var(--ed-surface); border: 1px solid var(--ed-border); border-radius: 18px;
        padding: 20px; box-shadow: 0 8px 24px rgba(15,23,42,.04);
    }
    .entity-quick__title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--ed-muted); margin-bottom: 14px; }
    .entity-quick__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .entity-quick__item {
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
        padding: 14px 10px; border-radius: 12px; background: var(--ed-bg); border: 1px solid var(--ed-border);
        text-decoration: none; color: var(--ed-ink); font-size: 0.78rem; font-weight: 600; text-align: center;
        transition: all .2s ease;
    }
    .entity-quick__item:hover { background: #fff; border-color: var(--ed-violet-mid); color: var(--ed-violet-mid); text-decoration: none; transform: translateY(-2px); }
    .entity-quick__item i { font-size: 1.2rem; }

    .entity-pill {
        display: flex; align-items: center; gap: 14px; padding: 18px 20px; border-radius: 14px;
        background: var(--ed-surface); border: 1px solid var(--ed-border); height: 100%;
        box-shadow: 0 6px 18px rgba(15,23,42,.04);
    }
    .entity-pill__icon {
        width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    .entity-pill--indigo .entity-pill__icon { background: rgba(79,70,229,.12); color: var(--ed-indigo); }
    .entity-pill--emerald .entity-pill__icon { background: rgba(16,185,129,.12); color: var(--ed-emerald); }
    .entity-pill--cyan .entity-pill__icon { background: rgba(14,165,233,.12); color: var(--ed-cyan); }
    .entity-pill__label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--ed-muted); }
    .entity-pill__value { font-size: 1.5rem; font-weight: 800; color: var(--ed-ink); }

    .entity-kpi {
        display: flex; gap: 14px; align-items: flex-start; padding: 20px; border-radius: 16px;
        background: var(--ed-surface); border: 1px solid var(--ed-border); height: 100%;
        box-shadow: 0 8px 24px rgba(15,23,42,.04); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        text-decoration: none; color: inherit;
    }
    .entity-kpi:hover { transform: translateY(-2px); text-decoration: none; color: inherit; }
    a.entity-kpi--clickable { cursor: pointer; }
    a.entity-kpi--clickable:hover {
        border-color: rgba(91,74,154,.35);
        box-shadow: 0 12px 28px rgba(55,32,91,.12);
    }
    .entity-kpi__cta {
        margin-top: 8px; font-size: 0.72rem; font-weight: 700; color: var(--ed-violet-mid);
        display: inline-flex; align-items: center; gap: 2px; opacity: .85;
    }
    a.entity-kpi--clickable:hover .entity-kpi__cta { opacity: 1; }
    a.entity-kpi--clickable.is-active {
        border-color: rgba(91,74,154,.45);
        box-shadow: 0 12px 28px rgba(55,32,91,.14);
        background: #faf8ff;
    }
    .entity-detail-panel { scroll-margin-top: 88px; }
    .entity-detail-panel.is-focused {
        outline: 2px solid rgba(91,74,154,.35);
        box-shadow: 0 0 0 6px rgba(91,74,154,.08);
    }
    .entity-detail-actions { gap: 8px; }
    .entity-detail-close {
        font-size: 1.35rem; line-height: 1; padding: 2px 10px !important; color: var(--ed-muted);
    }
    .entity-detail-close:hover { color: var(--ed-ink); }
    .entity-pagination {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
        padding: 14px 20px; border-top: 1px solid var(--ed-border); background: #fafbfc;
    }
    .entity-pagination__meta { font-size: 0.82rem; color: var(--ed-muted); }
    .entity-pagination__controls { display: flex; align-items: center; gap: 6px; }
    .entity-pagination__btn {
        border: 1px solid var(--ed-border); background: #fff; color: var(--ed-ink);
        border-radius: 8px; min-width: 34px; height: 34px; padding: 0 10px;
        font-size: 0.82rem; font-weight: 600; cursor: pointer;
    }
    .entity-pagination__btn:hover:not(:disabled) { border-color: var(--ed-violet-mid); color: var(--ed-violet-mid); }
    .entity-pagination__btn:disabled { opacity: .45; cursor: not-allowed; }
    .entity-pagination__btn.is-current {
        background: var(--ed-violet-mid); border-color: var(--ed-violet-mid); color: #fff;
    }
    #entity-kpi-details-wrap:not(:empty) .entity-detail-panel:not(.d-none) { margin-bottom: 1.5rem; }
    .entity-kpi--violet { border-top: 3px solid var(--ed-violet-mid); }
    .entity-kpi--cyan { border-top: 3px solid var(--ed-cyan); }
    .entity-kpi--amber { border-top: 3px solid var(--ed-amber); }
    .entity-kpi--emerald { border-top: 3px solid var(--ed-emerald); }
    .entity-kpi--indigo { border-top: 3px solid var(--ed-indigo); }
    .entity-kpi__icon {
        width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; background: rgba(91,74,154,.1); color: var(--ed-violet-mid); flex-shrink: 0;
    }
    .entity-kpi--cyan .entity-kpi__icon { background: rgba(14,165,233,.12); color: var(--ed-cyan); }
    .entity-kpi--amber .entity-kpi__icon { background: rgba(245,158,11,.14); color: var(--ed-amber); }
    .entity-kpi--emerald .entity-kpi__icon { background: rgba(16,185,129,.14); color: var(--ed-emerald); }
    .entity-kpi--indigo .entity-kpi__icon { background: rgba(79,70,229,.12); color: var(--ed-indigo); }
    .entity-kpi__label { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--ed-muted); }
    .entity-kpi__value { font-size: 1.75rem; font-weight: 800; color: var(--ed-ink); line-height: 1.1; }
    .entity-kpi__hint { font-size: 0.8rem; color: var(--ed-muted); margin-top: 4px; }

    .entity-panel {
        background: var(--ed-surface); border: 1px solid var(--ed-border); border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15,23,42,.04); overflow: hidden;
    }
    .entity-panel__head { padding: 22px 24px 0; }
    .entity-panel__head--row { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
    .entity-panel__head h3 { font-size: 1.05rem; font-weight: 800; color: var(--ed-ink); margin: 0 0 4px; }
    .entity-panel__head p { font-size: 0.84rem; color: var(--ed-muted); margin: 0; }
    .entity-panel__body { padding: 20px 24px 24px; }
    .entity-panel__body--chart { min-height: 280px; }

    .entity-chart-ring { position: relative; max-width: 220px; margin: 0 auto; }
    .entity-chart-ring__center {
        position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;
        pointer-events: none;
    }
    .entity-chart-ring__center strong { font-size: 1.6rem; font-weight: 800; color: var(--ed-ink); line-height: 1; }
    .entity-chart-ring__center span { font-size: 0.75rem; color: var(--ed-muted); text-transform: uppercase; letter-spacing: .04em; }

    .entity-legend__row {
        display: grid; grid-template-columns: 12px 1fr auto auto; gap: 10px; align-items: center;
        padding: 8px 0; border-bottom: 1px dashed var(--ed-border); font-size: 0.86rem;
    }
    .entity-legend__row:last-child { border-bottom: 0; }
    .entity-legend__dot { width: 10px; height: 10px; border-radius: 50%; }
    .entity-legend__count { font-weight: 700; color: var(--ed-ink); }
    .entity-legend__pct { color: var(--ed-muted); font-size: 0.8rem; min-width: 36px; text-align: right; }

    .entity-card-link {
        display: flex; align-items: center; gap: 14px; padding: 16px; border-radius: 14px;
        border: 1px solid var(--ed-border); background: var(--ed-bg); text-decoration: none; color: inherit;
        transition: all .2s ease; height: 100%;
    }
    .entity-card-link:hover { background: #fff; border-color: var(--ed-violet-mid); text-decoration: none; color: inherit; box-shadow: 0 8px 20px rgba(55,32,91,.08); }
    .entity-card-link__avatar {
        width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #37205B, #5b4a9a);
        color: #fff; font-weight: 800; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .entity-card-link__name { font-weight: 700; color: var(--ed-ink); }
    .entity-card-link__meta { font-size: 0.82rem; color: var(--ed-muted); }
    .entity-card-link__arrow { color: var(--ed-muted); margin-left: auto; }

    .entity-table thead th {
        border: 0; border-bottom: 1px solid var(--ed-border); background: #f8fafc;
        font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
        color: var(--ed-muted); padding: 14px 20px;
    }
    .entity-table tbody td { padding: 16px 20px; vertical-align: middle; border-color: #f1f5f9; }
    .entity-table__title { font-weight: 700; color: var(--ed-violet-mid); text-decoration: none; }
    .entity-table__title:hover { text-decoration: underline; }
    .entity-table__money { font-weight: 700; color: var(--ed-ink); }

    .entity-status {
        display: inline-block; padding: 5px 12px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;
    }
    .entity-status--in_progress { background: rgba(91,74,154,.12); color: #5b4a9a; }
    .entity-status--not_started { background: rgba(196,181,253,.35); color: #6d28d9; }
    .entity-status--completed { background: rgba(14,165,233,.12); color: #0284c7; }
    .entity-status--deferred { background: rgba(248,113,113,.15); color: #dc2626; }

    .entity-progress { height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin-bottom: 4px; }
    .entity-progress__bar { height: 100%; background: linear-gradient(90deg, #37205B, #5b4a9a); border-radius: 999px; }

    .entity-btn-outline {
        border: 1px solid var(--ed-violet-mid); color: var(--ed-violet-mid); border-radius: 999px; font-weight: 600;
    }
    .entity-btn-outline:hover { background: var(--ed-violet-mid); color: #fff; }
    .entity-btn-ghost { border-radius: 999px; font-weight: 600; color: var(--ed-violet-mid); background: rgba(91,74,154,.08); border: 0; }
    .entity-btn-ghost:hover { background: rgba(91,74,154,.15); color: var(--ed-violet); }

    .entity-empty { text-align: center; color: var(--ed-muted); padding: 32px 16px; }
    .entity-empty i { font-size: 2rem; display: block; margin-bottom: 10px; opacity: .45; }
    .entity-empty p { margin: 0; }
</style>
@endpush

@push('scripts')
<script type="text/javascript">
    (function () {
        'use strict';

        const status = @json($status);
        const statusTotal = @json($statusTotal);
        const deptData = @json($departmentHeadcount);

        const statusCtx = document.getElementById('entity_project_status_chart');
        if (statusCtx && statusTotal > 0 && typeof Chart !== 'undefined') {
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
                            status.in_progress || 0,
                            status.not_started || 0,
                            status.completed || 0,
                            status.deferred || 0,
                        ],
                        backgroundColor: ['#5b4a9a', '#c4b5fd', '#0ea5e9', '#f87171'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1,
                    cutoutPercentage: 72,
                    legend: { display: false },
                    tooltips: { backgroundColor: '#1e293b', cornerRadius: 8 },
                },
            });
        }

        const deptCtx = document.getElementById('entity_department_chart');
        if (deptCtx && deptData.length && typeof Chart !== 'undefined') {
            new Chart(deptCtx.getContext('2d'), {
                type: 'horizontalBar',
                data: {
                    labels: deptData.map(function (item) { return item.department; }),
                    datasets: [{
                        label: @json(trans('file.Employees')),
                        data: deptData.map(function (item) { return item.total; }),
                        backgroundColor: 'rgba(91, 74, 154, 0.85)',
                        borderWidth: 0,
                        barPercentage: 0.65,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.4,
                    legend: { display: false },
                    scales: {
                        xAxes: [{ ticks: { beginAtZero: true, precision: 0 }, gridLines: { color: '#f1f5f9' } }],
                        yAxes: [{ gridLines: { display: false }, ticks: { fontStyle: '600' } }],
                    },
                },
            });
        }

        function initEntityPanelPagination(panel) {
            var table = panel.querySelector('.entity-paged-table');
            var pager = panel.querySelector('.entity-pagination');
            if (!table || !pager) {
                return;
            }

            var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr.entity-page-row'));
            var pageSize = parseInt(panel.getAttribute('data-page-size') || '5', 10) || 5;
            var currentPage = 1;
            var totalPages = Math.max(1, Math.ceil(rows.length / pageSize));

            function render() {
                var start = (currentPage - 1) * pageSize;
                var end = start + pageSize;

                rows.forEach(function (row, index) {
                    row.style.display = (index >= start && index < end) ? '' : 'none';
                });

                var from = rows.length ? (start + 1) : 0;
                var to = Math.min(end, rows.length);
                var meta = from && to
                    ? (from + '–' + to + ' / ' + rows.length)
                    : ('0 / ' + rows.length);

                var controls = '';
                controls += '<button type="button" class="entity-pagination__btn" data-page="prev" ' + (currentPage <= 1 ? 'disabled' : '') + '>&laquo;</button>';

                for (var page = 1; page <= totalPages; page++) {
                    controls += '<button type="button" class="entity-pagination__btn' + (page === currentPage ? ' is-current' : '') + '" data-page="' + page + '">' + page + '</button>';
                }

                controls += '<button type="button" class="entity-pagination__btn" data-page="next" ' + (currentPage >= totalPages ? 'disabled' : '') + '>&raquo;</button>';

                pager.innerHTML =
                    '<div class="entity-pagination__meta">' + meta + '</div>' +
                    '<div class="entity-pagination__controls">' + controls + '</div>';
            }

            pager.onclick = function (event) {
                var button = event.target.closest('[data-page]');
                if (!button || button.disabled) {
                    return;
                }

                var action = button.getAttribute('data-page');
                if (action === 'prev') {
                    currentPage = Math.max(1, currentPage - 1);
                } else if (action === 'next') {
                    currentPage = Math.min(totalPages, currentPage + 1);
                } else {
                    currentPage = parseInt(action, 10) || 1;
                }

                render();
            };

            panel._entityResetPage = function () {
                currentPage = 1;
                render();
            };

            render();
        }

        document.querySelectorAll('.entity-detail-panel').forEach(initEntityPanelPagination);

        function hideAllEntityDetails() {
            document.querySelectorAll('.entity-detail-panel').forEach(function (panel) {
                panel.classList.add('d-none');
                panel.classList.remove('is-focused');
            });
            document.querySelectorAll('a.entity-kpi--clickable').forEach(function (card) {
                card.classList.remove('is-active');
            });
        }

        function showEntityDetail(hash, triggerCard) {
            if (!hash || hash.charAt(0) !== '#') {
                return;
            }

            var target = document.querySelector(hash);
            if (!target || !target.classList.contains('entity-detail-panel')) {
                return;
            }

            hideAllEntityDetails();
            target.classList.remove('d-none');
            target.classList.add('is-focused');

            if (typeof target._entityResetPage === 'function') {
                target._entityResetPage();
            }

            if (triggerCard) {
                triggerCard.classList.add('is-active');
            } else {
                var linkedCard = document.querySelector('a.entity-kpi--clickable[href="' + hash + '"]');
                if (linkedCard) {
                    linkedCard.classList.add('is-active');
                }
            }

            setTimeout(function () {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 40);

            setTimeout(function () {
                target.classList.remove('is-focused');
            }, 1600);
        }

        document.querySelectorAll('a.entity-kpi--clickable[href^="#"]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                var hash = this.getAttribute('href');
                history.replaceState(null, '', hash);
                showEntityDetail(hash, this);
            });
        });

        document.querySelectorAll('.entity-detail-close').forEach(function (button) {
            button.addEventListener('click', function () {
                hideAllEntityDetails();
                if (window.location.hash) {
                    history.replaceState(null, '', window.location.pathname + window.location.search);
                }
            });
        });

        if (window.location.hash && document.querySelector(window.location.hash + '.entity-detail-panel')) {
            showEntityDetail(window.location.hash);
        }
    })();
</script>
@endpush
