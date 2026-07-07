<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Operations Summary Report') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 18px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0 0 4px;
            font-size: 18px;
            color: #1e3a5f;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 9px;
        }
        .stats {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .stats td {
            width: 25%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            text-align: center;
            background: #f8fafc;
        }
        .stats .label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.04em;
        }
        .stats .value {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            margin-top: 4px;
        }
        h2 {
            font-size: 12px;
            color: #1e3a5f;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.data th,
        table.data td {
            border: 1px solid #e2e8f0;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.data th {
            background: #eff6ff;
            color: #1e3a5f;
            font-size: 8px;
            text-transform: uppercase;
        }
        .level-company { background: #eef2ff; font-weight: bold; }
        .level-client { background: #f0f9ff; padding-left: 12px; }
        .level-project { padding-left: 22px; }
        .level-employee { padding-left: 32px; font-size: 9px; color: #475569; }
        .muted { color: #64748b; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('Operations Summary Report') }}</h1>
        <p>{{ __('Generated') }}: {{ $generatedAt }} &nbsp;|&nbsp; {{ __('Filters') }}: {{ $filterSummary }}</p>
    </div>

    <table class="stats">
        <tr>
            <td>
                <div class="label">{{ trans('file.Company') }}</div>
                <div class="value">{{ $data['totals']['companies'] ?? 0 }}</div>
            </td>
            <td>
                <div class="label">{{ trans('file.Client') }}</div>
                <div class="value">{{ $data['totals']['clients'] ?? 0 }}</div>
            </td>
            <td>
                <div class="label">{{ trans('file.Projects') }}</div>
                <div class="value">{{ $data['totals']['projects'] ?? 0 }}</div>
            </td>
            <td>
                <div class="label">{{ __('Deployed Employees') }}</div>
                <div class="value">{{ $data['totals']['deployed_resources'] ?? 0 }}</div>
            </td>
        </tr>
    </table>

    <h2>{{ __('Project Status') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ trans('file.Status') }}</th>
                <th>{{ __('Count') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach (($data['project_status_totals'] ?? []) as $status => $count)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $status)) }}</td>
                    <td>{{ $count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('Company → Client → Project → Employee') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 28%;">{{ __('Hierarchy') }}</th>
                <th style="width: 12%;">{{ trans('file.Projects') }}</th>
                <th style="width: 12%;">{{ __('Employees') }}</th>
                <th style="width: 48%;">{{ __('Details') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($data['companies'] ?? []) as $company)
                <tr class="level-company">
                    <td>{{ trans('file.Company') }}: {{ $company['name'] }}</td>
                    <td>{{ $company['total_projects'] ?? 0 }}</td>
                    <td>{{ $company['deployed_resources'] ?? 0 }}</td>
                    <td>{{ $company['clients_count'] ?? 0 }} {{ trans('file.Client') }}</td>
                </tr>
                @foreach (($company['clients'] ?? []) as $client)
                    <tr class="level-client">
                        <td>{{ trans('file.Client') }}: {{ $client['name'] }}</td>
                        <td>{{ $client['projects_count'] ?? 0 }}</td>
                        <td>{{ $client['deployed_resources'] ?? 0 }}</td>
                        <td></td>
                    </tr>
                    @foreach (($client['projects'] ?? []) as $project)
                        <tr class="level-project">
                            <td>{{ trans('file.Project') }}: {{ $project['title'] }}</td>
                            <td>1</td>
                            <td>{{ $project['deployed_count'] ?? 0 }}</td>
                            <td><span class="muted">{{ $project['status_label'] ?? '' }}</span></td>
                        </tr>
                        @foreach (($project['employees'] ?? []) as $employee)
                            <tr class="level-employee">
                                <td>{{ trans('file.Employee') }}: {{ $employee['name'] }}</td>
                                <td></td>
                                <td>1</td>
                                <td>
                                    {{ $employee['staff_id'] }} · {{ $employee['designation'] }} ·
                                    {{ $employee['department'] }} · {{ $employee['location'] }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
            @empty
                <tr>
                    <td colspan="4" class="muted">{{ __('No data found.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>{{ __('Resource Deployment Matrix') }}</h2>
    <table class="data">
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
        <tbody>
            @forelse (($data['deployments'] ?? []) as $deployment)
                @php $projects = $deployment['projects'] ?? []; @endphp
                @if ($projects === [])
                    <tr>
                        <td>{{ $deployment['employee_name'] }}</td>
                        <td>{{ $deployment['staff_id'] }}</td>
                        <td>{{ $deployment['company'] }}</td>
                        <td>{{ $deployment['client'] }}</td>
                        <td>{{ $deployment['location'] }}</td>
                        <td>{{ $deployment['department'] }}</td>
                        <td>{{ $deployment['designation'] }}</td>
                        <td>---</td>
                    </tr>
                @else
                    @foreach ($projects as $index => $project)
                        <tr>
                            <td>{{ $index === 0 ? $deployment['employee_name'] : '' }}</td>
                            <td>{{ $index === 0 ? $deployment['staff_id'] : '' }}</td>
                            <td>{{ $project['company'] ?? $deployment['company'] }}</td>
                            <td>{{ $index === 0 ? $deployment['client'] : '' }}</td>
                            <td>{{ $index === 0 ? $deployment['location'] : '' }}</td>
                            <td>{{ $project['department'] ?? $deployment['department'] }}</td>
                            <td>{{ $index === 0 ? $deployment['designation'] : '' }}</td>
                            <td>{{ $project['title'] ?? '---' }}</td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="8" class="muted">{{ __('No deployment data found.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
