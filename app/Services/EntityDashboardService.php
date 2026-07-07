<?php

namespace App\Services;

use App\Models\Client;
use App\Models\company;
use App\Models\department;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Project;
use App\Support\ClientDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EntityDashboardService
{
    public function buildCompanyDashboard(company $company): array
    {
        $companyId = (int) $company->id;
        $companyName = trim((string) $company->company_name);

        $projectsQuery = Project::query()->where('company_id', $companyId);
        $projectIds = (clone $projectsQuery)->pluck('id');
        $clientIds = (clone $projectsQuery)->whereNotNull('client_id')->pluck('client_id')->unique()->filter();

        $clients = Client::query()
            ->where(function ($query) use ($companyName, $clientIds) {
                if ($companyName !== '') {
                    $query->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower($companyName)]);
                }

                if ($clientIds->isNotEmpty()) {
                    $query->orWhereIn('id', $clientIds);
                }
            })
            ->orderBy('first_name')
            ->get();

        $activeEmployees = Employee::query()
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->count();

        $recentProjects = (clone $projectsQuery)
            ->with('client:id,first_name,last_name')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Project $project) => $this->mapProjectRow($project));

        $statusCounts = $this->countProjectsByStatus($projectsQuery);
        $projectTotal = max(1, array_sum($statusCounts));
        $totalRevenueRaw = (float) (clone $projectsQuery)->sum('total_revenue');

        $departmentHeadcount = DB::table('employees')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->where('employees.company_id', $companyId)
            ->where('employees.is_active', 1)
            ->whereNull('employees.exit_date')
            ->groupBy('departments.id', 'departments.department_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get([
                'departments.department_name as department',
                DB::raw('COUNT(employees.id) as total'),
            ])
            ->map(fn ($row) => [
                'department' => $row->department,
                'total' => (int) $row->total,
            ]);

        return [
            'type' => 'company',
            'entity_id' => $companyId,
            'title' => $company->company_name,
            'subtitle' => $company->Location->location_name ?? __('Company overview'),
            'type_label' => trans('file.Company'),
            'logo_url' => $company->company_logo
                ? asset('uploads/company_logo/'.$company->company_logo)
                : null,
            'initials' => $this->initials($company->company_name),
            'meta' => array_filter([
                __('Email') => $company->email,
                __('Phone') => $company->contact_no,
                trans('file.Country') => $company->Location->Country->name ?? null,
                __('Company Type') => $company->companyType->type_name ?? null,
                trans('file.Website') => $company->website,
            ]),
            'back_url' => route('companies.index'),
            'back_label' => trans('file.Company'),
            'featured' => [
                'label' => __('Total Revenue'),
                'value' => $this->formatMoney($totalRevenueRaw),
                'meta' => $projectIds->count().' '.__('active portfolio projects'),
            ],
            'highlights' => [
                [
                    'label' => __('In Progress'),
                    'value' => $statusCounts['in_progress'],
                    'icon' => 'dripicons-media-play',
                    'tone' => 'indigo',
                ],
                [
                    'label' => __('Completed'),
                    'value' => $statusCounts['completed'],
                    'icon' => 'dripicons-checkmark',
                    'tone' => 'emerald',
                ],
                [
                    'label' => __('Completion Rate'),
                    'value' => round(($statusCounts['completed'] / $projectTotal) * 100).'%',
                    'icon' => 'dripicons-graph-bar',
                    'tone' => 'cyan',
                ],
            ],
            'kpis' => [
                ['label' => trans('file.Employees'), 'value' => $activeEmployees, 'icon' => 'dripicons-user-group', 'tone' => 'violet', 'hint' => __('Active workforce')],
                ['label' => trans('file.Client'), 'value' => $clients->count(), 'icon' => 'dripicons-briefcase', 'tone' => 'cyan', 'hint' => __('Linked accounts')],
                ['label' => trans('file.Projects'), 'value' => $projectIds->count(), 'icon' => 'dripicons-checklist', 'tone' => 'amber', 'hint' => __('Total projects')],
                ['label' => trans('file.Department'), 'value' => department::query()->where('company_id', $companyId)->count(), 'icon' => 'dripicons-network-3', 'tone' => 'indigo', 'hint' => __('Organizational units')],
                ['label' => __('Project Categories'), 'value' => Project::query()->where('company_id', $companyId)->whereNotNull('project_category_id')->distinct()->count('project_category_id'), 'icon' => 'dripicons-folder', 'tone' => 'emerald', 'hint' => __('Service lines')],
            ],
            'quick_links' => array_values(array_filter([
                ['label' => trans('file.Employees'), 'url' => route('employees.index'), 'icon' => 'dripicons-user-group'],
                ['label' => trans('file.Projects'), 'url' => route('projects.index'), 'icon' => 'dripicons-checklist'],
                ['label' => trans('file.Client'), 'url' => route('clients.index'), 'icon' => 'dripicons-briefcase'],
                ['label' => __('Project Categories'), 'url' => route('project_categories.index'), 'icon' => 'dripicons-folder'],
            ])),
            'charts' => [
                'project_status' => $statusCounts,
                'status_breakdown' => $this->statusBreakdown($statusCounts),
                'department_headcount' => $departmentHeadcount,
            ],
            'recent_projects' => $recentProjects,
            'related' => $clients->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => ClientDisplay::label($client),
                'meta' => (clone $projectsQuery)->where('client_id', $client->id)->count().' '.__('projects'),
                'url' => route('clients.dashboard', $client->id),
                'initials' => $this->initials(ClientDisplay::label($client)),
            ])->values(),
            'related_title' => trans('file.Client'),
        ];
    }

    public function buildClientDashboard(Client $client): array
    {
        $clientId = (int) $client->id;
        $projectsQuery = Project::query()->where('client_id', $clientId);

        $assignedEmployees = Employee::query()
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->whereHas('projects', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->count();

        $totalClientEmployees = Employee::query()
            ->where('client_id', $clientId)
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->count();

        $categoriesCount = (clone $projectsQuery)->whereNotNull('project_category_id')->distinct()->count('project_category_id');

        $recentProjects = (clone $projectsQuery)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Project $project) => $this->mapProjectRow($project));

        $company = company::query()
            ->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower(trim((string) $client->company_name))])
            ->first();

        $statusCounts = $this->countProjectsByStatus($projectsQuery);
        $projectTotal = max(1, array_sum($statusCounts));
        $totalRevenueRaw = (float) (clone $projectsQuery)->sum('total_revenue');

        return [
            'type' => 'client',
            'entity_id' => $clientId,
            'title' => ClientDisplay::label($client),
            'subtitle' => $client->company_name ?: __('Client overview'),
            'type_label' => trans('file.Client'),
            'logo_url' => $client->profile
                ? asset('uploads/profile_photos/'.$client->profile)
                : null,
            'initials' => $this->initials(ClientDisplay::label($client)),
            'meta' => array_filter([
                trans('file.Email') => $client->email,
                trans('file.Phone') => $client->contact_no !== '-' ? $client->contact_no : null,
                trans('file.Company') => $client->company_name,
                trans('file.Website') => $client->website,
            ]),
            'back_url' => route('clients.index'),
            'back_label' => trans('file.Client'),
            'company_dashboard_url' => $company ? route('companies.dashboard', $company->id) : null,
            'featured' => [
                'label' => __('Total Revenue'),
                'value' => $this->formatMoney($totalRevenueRaw),
                'meta' => (clone $projectsQuery)->count().' '.__('projects in portfolio'),
            ],
            'highlights' => [
                [
                    'label' => __('In Progress'),
                    'value' => $statusCounts['in_progress'],
                    'icon' => 'dripicons-media-play',
                    'tone' => 'indigo',
                ],
                [
                    'label' => __('Completed'),
                    'value' => $statusCounts['completed'],
                    'icon' => 'dripicons-checkmark',
                    'tone' => 'emerald',
                ],
                [
                    'label' => __('Completion Rate'),
                    'value' => round(($statusCounts['completed'] / $projectTotal) * 100).'%',
                    'icon' => 'dripicons-graph-bar',
                    'tone' => 'cyan',
                ],
            ],
            'kpis' => [
                ['label' => trans('file.Projects'), 'value' => (clone $projectsQuery)->count(), 'icon' => 'dripicons-checklist', 'tone' => 'amber', 'hint' => __('Total projects')],
                ['label' => __('Project Categories'), 'value' => $categoriesCount, 'icon' => 'dripicons-folder', 'tone' => 'emerald', 'hint' => __('Service lines')],
                ['label' => trans('file.Employees'), 'value' => $totalClientEmployees, 'icon' => 'dripicons-user-id', 'tone' => 'indigo', 'hint' => __('Active under this client')],
                ['label' => __('Assigned Employees'), 'value' => $assignedEmployees, 'icon' => 'dripicons-user-group', 'tone' => 'violet', 'hint' => __('On active projects')],
                ['label' => trans('file.Invoice'), 'value' => Invoice::query()->where('client_id', $clientId)->count(), 'icon' => 'dripicons-document', 'tone' => 'cyan', 'hint' => __('Billing records')],
            ],
            'quick_links' => array_values(array_filter([
                ['label' => trans('file.Projects'), 'url' => route('projects.index'), 'icon' => 'dripicons-checklist'],
                $company ? ['label' => trans('file.Company'), 'url' => route('companies.dashboard', $company->id), 'icon' => 'dripicons-store'] : null,
            ])),
            'charts' => [
                'project_status' => $statusCounts,
                'status_breakdown' => $this->statusBreakdown($statusCounts),
                'department_headcount' => collect(),
            ],
            'recent_projects' => $recentProjects,
        ];
    }

    protected function statusBreakdown(array $counts): array
    {
        $total = max(1, array_sum($counts));

        return [
            ['key' => 'in_progress', 'label' => __('In Progress'), 'count' => $counts['in_progress'], 'color' => '#5b4a9a', 'percent' => round(($counts['in_progress'] / $total) * 100)],
            ['key' => 'not_started', 'label' => __('Not Started'), 'count' => $counts['not_started'], 'color' => '#c4b5fd', 'percent' => round(($counts['not_started'] / $total) * 100)],
            ['key' => 'completed', 'label' => __('Completed'), 'count' => $counts['completed'], 'color' => '#0ea5e9', 'percent' => round(($counts['completed'] / $total) * 100)],
            ['key' => 'deferred', 'label' => __('Deferred'), 'count' => $counts['deferred'], 'color' => '#f87171', 'percent' => round(($counts['deferred'] / $total) * 100)],
        ];
    }

    protected function initials(string $label): string
    {
        $parts = preg_split('/\s+/', trim($label)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'HR';
    }

    protected function mapProjectRow(Project $project): array
    {
        $status = $this->normalizeProjectStatus($project->project_status);

        return [
            'id' => $project->id,
            'title' => $project->title,
            'status' => $status,
            'status_label' => ucwords(str_replace('_', ' ', $status)),
            'status_class' => 'entity-status--'.$status,
            'client' => $project->client ? ClientDisplay::label($project->client) : '—',
            'revenue' => $this->formatMoney($project->total_revenue ?? 0),
            'progress' => is_numeric($project->project_progress) ? (int) $project->project_progress : 0,
            'url' => route('projects.show', $project->id),
        ];
    }

    protected function countProjectsByStatus(Builder $query): array
    {
        $counts = [
            'in_progress' => 0,
            'not_started' => 0,
            'completed' => 0,
            'deferred' => 0,
        ];

        foreach ((clone $query)->select('project_status')->cursor() as $project) {
            $status = $this->normalizeProjectStatus($project->project_status);

            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    protected function normalizeProjectStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        if ($status === '' || in_array($status, ['not started', 'not_started'], true)) {
            return 'not_started';
        }

        return $status;
    }

    protected function formatMoney($amount): string
    {
        $formatted = number_format((float) $amount, 2, '.', ',');

        if (config('variable.currency_format') === 'suffix') {
            return $formatted.config('variable.currency');
        }

        return config('variable.currency').$formatted;
    }
}
