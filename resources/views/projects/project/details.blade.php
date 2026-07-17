@extends('layout.main')
@section('content')



    <section class="pd-page">
        @php
            $statusKey = strtolower(str_replace(' ', '_', (string) ($project->project_status ?? 'not_started')));
            $statusLabel = ucwords(str_replace('_', ' ', $statusKey));
            $progressValue = (int) ($project->project_progress ?? 0);
            $revenueRaw = (float) ($project->total_revenue ?? 0);
            $revenueFormatted = config('variable.currency_format') === 'suffix'
                ? number_format($revenueRaw, 2).config('variable.currency')
                : config('variable.currency').number_format($revenueRaw, 2);
            $clientName = trim(($project->client->first_name ?? '').' '.($project->client->last_name ?? ''));
        @endphp

        <div class="container-fluid">
            <div class="pd-hero mb-4">
                <div class="pd-hero__top">
                    <nav class="pd-breadcrumb">
                        <a href="{{ route('projects.index') }}">{{ trans('file.Projects') }}</a>
                        <span>/</span>
                        <span>{{ __('Details') }}</span>
                    </nav>
                    <span class="pd-status pd-status--{{ $statusKey }}">{{ $statusLabel }}</span>
                </div>
                <div class="pd-hero__body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                        <div class="pd-hero__main">
                            <h1 class="pd-hero__title">{{ $project->title }}</h1>
                            @if ($project->summary)
                                <p class="pd-hero__summary mb-0">{{ $project->summary }}</p>
                            @endif
                        </div>
                        <div class="pd-hero__progress-box">
                            <div class="pd-hero__progress-label">{{ __('Progress') }}</div>
                            <div class="pd-hero__progress-value">{{ $progressValue }}%</div>
                            <div class="pd-progress">
                                <div class="pd-progress__bar" style="width:{{ $progressValue }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="pd-stat">
                        <div class="pd-stat__label">{{ trans('file.Client') }}</div>
                        <div class="pd-stat__value">{{ $clientName !== '' ? $clientName : '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="pd-stat">
                        <div class="pd-stat__label">{{ __('Project Category') }}</div>
                        <div class="pd-stat__value">{{ $project->projectCategory->category_name ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="pd-stat">
                        <div class="pd-stat__label">{{ __('Timeline') }}</div>
                        <div class="pd-stat__value pd-stat__value--sm">{{ $project->start_date }} → {{ $project->end_date ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="pd-stat">
                        <div class="pd-stat__label">{{ __('Total Revenue') }}</div>
                        <div class="pd-stat__value">{{ $revenueFormatted }}</div>
                    </div>
                </div>
            </div>

            <div class="pd-panel mb-4">
                <div class="pd-panel__head">
                    <h3>{{ __('Assigned Project Leads') }}</h3>
                    <p>{{ __('Team members linked to this project') }}</p>
                </div>
                <div class="pd-panel__body">
                    <span id="assigned_result"></span>
                    <form method="post" id="assigned_form" class="form-horizontal">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-10">
                                <select name="employee_id[]" id="employee_id" class="form-control js-example-responsive" multiple="multiple">
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @can('assign-project')
                                <div class="col-md-2 mt-3 mt-md-0">
                                    <input type="submit" name="assigned_submit" id="assigned_submit" class="btn btn-success btn-block" value="{{ trans('file.Save') }}">
                                </div>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <div class="pd-panel">
                <div class="pd-panel__body pt-0">
                    <ul class="nav nav-tabs pd-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="details-tab" data-toggle="tab" href="#Details"
                                       role="tab" aria-controls="Details"
                                       aria-selected="true">{{trans('file.Overview')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="discussions-tab" data-toggle="tab" href="#Discussions"
                                       role="tab"
                                       aria-controls="Discussions" data-table="discussion"
                                       aria-selected="false">{{trans('file.Discussions')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="progress-tab" data-toggle="tab" href="#Progress" role="tab"
                                       aria-controls="Progress" data-table="progress"
                                       aria-selected="false">{{trans('Progress')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="files-tab" data-toggle="tab" href="#Files" role="tab"
                                       aria-controls="Files" data-table="files"
                                       aria-selected="false">{{trans('file.Files')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="notes-tab" data-toggle="tab" href="#Notes" role="tab"
                                       aria-controls="Notes" aria-selected="false">{{trans('file.Notes')}}</a>
                                </li>
                            </ul>
                            <div class="tab-content pd-tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="Details" role="tabpanel"
                                     aria-labelledby="details-tab">
                                    <div class="pd-overview" id="project_description"></div>
                                </div>

                                <div class="tab-pane fade" id="Discussions" role="tabpanel"
                                     aria-labelledby="discussions-tab">
                                    <div class="pd-tab-toolbar">
                                        <span id="discussions_result"></span>
                                        <a class="btn btn-primary" data-toggle="collapse" href="#collapseDiscussion" role="button" aria-expanded="false" aria-controls="collapseDiscussion">
                                            {{__('Post A Message')}}
                                        </a>
                                    </div>
                                    <div class="collapse" id="collapseDiscussion">
                                        <hr>
                                        <form method="post" id="discussions_form" class="form-horizontal" enctype="multipart/form-data">
                                            @csrf
                                            <div class="form-group">
                                                <label>{{trans('file.Discussions')}}</label>
                                                <textarea required class="form-control" id="project_discussions" name="project_discussions" rows="3"></textarea>
                                            </div>

                                            <div class="form-group">
                                                <label>{{trans('file.Attachments')}} </label>
                                                <input type="file" name="discussion_attachments" id="discussion_attachments" class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <input type="submit" name="discussions_submit" id="discussions_submit" class="btn btn-success" value={{trans("file.Save")}}>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="table-responsive mt-3">
                                        <table id="discussions-table" class="table">
                                            <thead>
                                            <tr>
                                                <th>{{trans('file.User')}}</th>
                                                <th>{{trans('file.Message')}}</th>
                                                <th class="not-exported">{{trans('file.action')}}</th>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="Progress" role="tabpanel" aria-labelledby="progress-tab">
                                    <span id="progress_result"></span>
                                    <div class="pd-auto-note">
                                        <i class="dripicons-information"></i>
                                        <span>
                                            {{ __('Progress is calculated automatically from the project start and end dates.') }}
                                            <strong>{{ $progressValue }}%</strong>
                                        </span>
                                    </div>
                                    <form method="post" id="progress_form" class="form-horizontal">
                                        @csrf
                                        <input type="hidden" name="project_progress" value="{{ $progressValue }}">
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label>{{trans('file.Status')}}</label>
                                                <select name="project_status" id="project_status"
                                                        class="form-control selectpicker"
                                                        data-live-search="true" data-live-search-style="contains"
                                                        title='{{__('Selecting',['key'=>trans('file.Status')])}}...'>
                                                    <option value="not_started">{{__('Not Started')}}</option>
                                                    <option value="in_progress">{{__('In Progress')}}</option>
                                                    <option value="completed">{{trans('file.Completed')}}</option>
                                                    <option value="deferred">{{trans('file.Deferred')}}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>{{trans('file.Priority')}}</label>
                                                <select name="project_priority" id="project_priority"
                                                        class="form-control selectpicker"
                                                        data-live-search="true" data-live-search-style="contains"
                                                        title='{{__('Selecting',['key'=>trans('file.Priority')])}}...'>
                                                    <option value="low">{{trans('file.Low')}}</option>
                                                    <option value="medium">{{trans('file.Medium')}}</option>
                                                    <option value="high">{{trans('file.High')}}</option>
                                                    <option value="highest">{{trans('file.Highest')}}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <input type="submit" name="project_progress_submit" id="project_progress_submit"
                                                   class="btn btn-success" value="{{ trans('file.Save') }}">
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="Files" role="tabpanel" aria-labelledby="files-tab">
                                    <div class="pd-tab-toolbar">
                                        <span id="files_result"></span>
                                        <a class="btn btn-primary" data-toggle="collapse" href="#collapseFile" role="button" aria-expanded="false" aria-controls="collapseFile">
                                            {{__('Insert A File')}}
                                        </a>
                                    </div>
                                    <div class="collapse" id="collapseFile">
                                        <hr>
                                        <form method="post" id="files_form" class="form-horizontal"
                                              enctype="multipart/form-data">
                                            @csrf

                                            <div class="form-group">
                                                <label>{{trans('file.Title')}} *</label>
                                                <input type="text" name="file_title" id="file_title" required
                                                       class="form-control"
                                                       placeholder="{{trans('file.Title')}}">
                                            </div>

                                            <div class="form-group">
                                                <label>{{trans('file.Description')}}</label>
                                                <textarea required class="form-control" id="file_description"
                                                          name="file_description" rows="3"></textarea>
                                            </div>

                                            <div class="form-group">
                                                <label>{{trans('file.Attachments')}} </label>
                                                <input type="file" name="file_attachment" id="file_attachment"
                                                       class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <input type="submit" name="file_submit" id="file_submit"
                                                       class="btn btn-success" value={{trans("file.Save")}}>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="table-responsive mt-3">
                                        <table id="files-table" class="table">
                                            <thead>
                                                <tr>
                                                    <th>{{trans('file.Title')}}</th>
                                                    <th>{{trans('file.Description')}}</th>
                                                    <th>{{__('Date and Time')}}</th>
                                                    <th class="not-exported">{{trans('file.action')}}</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>


                                <div class="tab-pane fade" id="Notes" role="tabpanel" aria-labelledby="notes-tab">
                                    <div class="row">
                                        <div class="col-md-10">
                                            <span id="note_result"></span>
                                            <form method="post" id="note_form" class="form-horizontal">
                                                @csrf
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <label>{{__('Project Note')}}</label>
                                                        <textarea required class="form-control" id="project_note" name="project_note" rows="3">{{$project->project_note}}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <input type="submit" name="project_note_submit" id="project_note_submit" class="btn btn-success" value={{trans("file.Save")}}>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('css')
<style>
    .pd-page {
        --pd-violet: #37205B;
        --pd-violet-mid: #5b4a9a;
        --pd-cyan: #0ea5e9;
        --pd-ink: #0f172a;
        --pd-muted: #64748b;
        --pd-border: #e2e8f0;
        --pd-bg: #f8fafc;
        --pd-surface: #ffffff;
    }

    .pd-hero {
        background: linear-gradient(135deg, #37205B 0%, #5b4a9a 55%, #7c6bb8 100%);
        border-radius: 20px;
        padding: 24px 28px;
        color: #fff;
        box-shadow: 0 16px 40px rgba(55, 32, 91, 0.22);
    }
    .pd-hero__top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }
    .pd-breadcrumb {
        font-size: 0.84rem;
        opacity: 0.9;
    }
    .pd-breadcrumb a { color: #fff; text-decoration: none; }
    .pd-breadcrumb a:hover { text-decoration: underline; }
    .pd-breadcrumb span { margin: 0 6px; opacity: 0.65; }
    .pd-status {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.28);
    }
    .pd-status--in_progress { background: rgba(255, 255, 255, 0.22); }
    .pd-status--completed { background: rgba(14, 165, 233, 0.35); border-color: rgba(14, 165, 233, 0.5); }
    .pd-status--not_started { background: rgba(196, 181, 253, 0.35); }
    .pd-status--deferred { background: rgba(248, 113, 113, 0.35); border-color: rgba(248, 113, 113, 0.5); }
    .pd-hero__title {
        font-size: 1.75rem;
        font-weight: 800;
        margin: 0 0 8px;
        line-height: 1.2;
    }
    .pd-hero__summary {
        font-size: 0.95rem;
        opacity: 0.88;
        max-width: 640px;
    }
    .pd-hero__progress-box {
        min-width: 180px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 14px;
        padding: 14px 18px;
    }
    .pd-hero__progress-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.8;
        margin-bottom: 4px;
    }
    .pd-hero__progress-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 10px;
    }
    .pd-progress {
        height: 8px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 999px;
        overflow: hidden;
    }
    .pd-progress__bar {
        height: 100%;
        background: linear-gradient(90deg, #fff, rgba(255, 255, 255, 0.75));
        border-radius: 999px;
        transition: width 0.4s ease;
    }

    .pd-stat {
        background: var(--pd-surface);
        border: 1px solid var(--pd-border);
        border-radius: 16px;
        padding: 20px;
        height: 100%;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }
    .pd-stat__label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--pd-muted);
        margin-bottom: 8px;
    }
    .pd-stat__value {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--pd-ink);
        line-height: 1.3;
    }
    .pd-stat__value--sm { font-size: 0.92rem; font-weight: 700; }

    .pd-panel {
        background: var(--pd-surface);
        border: 1px solid var(--pd-border);
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }
    .pd-panel__head {
        padding: 22px 24px 0;
    }
    .pd-panel__head h3 {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--pd-ink);
        margin: 0 0 4px;
    }
    .pd-panel__head p {
        font-size: 0.84rem;
        color: var(--pd-muted);
        margin: 0;
    }
    .pd-panel__body { padding: 20px 24px 24px; }

    .pd-tabs {
        border-bottom: 1px solid var(--pd-border);
        padding: 0 8px;
        margin-bottom: 0;
    }
    .pd-tabs .nav-link {
        border: 0;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: var(--pd-muted);
        font-weight: 700;
        font-size: 0.86rem;
        padding: 16px 18px;
        margin-bottom: -1px;
        transition: color 0.2s ease, border-color 0.2s ease;
    }
    .pd-tabs .nav-link:hover {
        color: var(--pd-violet-mid);
        background: transparent;
    }
    .pd-tabs .nav-link.active {
        color: var(--pd-violet-mid);
        background: transparent;
        border-bottom-color: var(--pd-violet-mid);
    }
    .pd-tab-content { padding: 24px 16px 8px; }
    .pd-overview {
        background: var(--pd-bg);
        border: 1px solid var(--pd-border);
        border-radius: 14px;
        padding: 20px 22px;
        color: var(--pd-ink);
        line-height: 1.65;
        min-height: 120px;
    }
    .pd-tab-toolbar { margin-bottom: 16px; }
    .pd-tab-toolbar .btn-primary {
        background: var(--pd-violet-mid);
        border-color: var(--pd-violet-mid);
        border-radius: 10px;
        font-weight: 600;
    }
    .pd-auto-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: rgba(91, 74, 154, 0.08);
        border: 1px solid rgba(91, 74, 154, 0.18);
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        color: var(--pd-ink);
    }
    .pd-auto-note i {
        color: var(--pd-violet-mid);
        font-size: 1.1rem;
        margin-top: 2px;
    }

    .pd-page .table thead th {
        border: 0;
        border-bottom: 1px solid var(--pd-border);
        background: #f8fafc;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--pd-muted);
    }
    .pd-page .table tbody td {
        vertical-align: middle;
        border-color: #f1f5f9;
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript">
    (function($) {
        "use strict";

        let project_status = <?php echo json_encode($project->project_status) ?>;
        let project_priority = <?php echo json_encode($project->project_priority) ?>;
        let assigned = <?php echo json_encode($name) ?>;
        let description = <?php echo json_encode($project->description) ?>;

        function htmlDecode(input){
            var e = document.createElement('div');
            e.innerHTML = input;
            return e.childNodes.length == 0 ? "" : e.childNodes[0].nodeValue;
        }

        $('#project_status').val(project_status);
        $('#project_priority').val(project_priority);
        $('#project_description').html(htmlDecode(description));


        $(document).ready(function () {

            let date = $('.date');
            date.datepicker({
                format: @json(config('variable.date_format_js', 'dd-mm-yyyy')),
                autoclose: true,
                todayHighlight: true
            });


            $('.js-example-responsive').select2({
                placeholder: '{{__('')}}',
                width: 'resolve',
                theme: "classic",
            });
            if (Array.isArray(assigned) && assigned.length) {
                $('#employee_id').val(assigned.map(String)).trigger('change');
            }



            $('#assigned_form').on('submit', function (event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('projects.assigned',$project) }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (let count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                        }
                        $('#assigned_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })

            });
        });

        $('#progress_form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('project_progress.store',$project) }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function (data) {
                    let html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (let count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                    }
                    $('#progress_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            })
        });

        $('[data-table="discussion"]').one('click', function (e) {

            $('#discussions-table').DataTable().clear().destroy();

            let table_table = $('#discussions-table').DataTable({
                initComplete: function () {
                    this.api().columns([1]).every(function () {
                        var column = this;
                        var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                    $(this).val()
                                );

                                column
                                    .search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                            });

                        column.data().unique().sort().each(function (d, j) {
                            select.append('<option value="' + d + '">' + d + '</option>');
                            $('select').selectpicker('refresh');
                        });
                    });
                },
                responsive: true,
                fixedHeader: {
                    header: true,
                    footer: true
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('project_discussions.index',$project) }}",
                    method: "post"
                },

                columns: [


                    {
                        data: 'user',
                        name: 'user'
                    },
                    {
                        data: 'message',
                        name: 'message',
                        render: function (data, type, row) {
                            return data + ' (' + row.created_at + ')';
                        }

                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false
                    }
                ],


                "order": [],
                'language': {
                    'lengthMenu': '_MENU_ {{__("records per page")}}',
                    "info": '{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)',
                    "search": '{{trans("file.Search")}}',
                    'paginate': {
                        'previous': '{{trans("file.Previous")}}',
                        'next': '{{trans("file.Next")}}'
                    }
                },
                'columnDefs': [
                    {
                        "orderable": false,
                        'targets': [0, 2],
                    },
                ],

                'select': {style: 'multi', selector: 'td:first-child'},
                'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            });
            new $.fn.dataTable.FixedHeader(table_table);
        });

        $('#discussions_form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('project_discussions.store',$project) }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function (data) {
                    let html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (let count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                        $('#discussions_form')[0].reset();
                        $('#discussions-table').DataTable().ajax.reload();
                    }
                    $('#discussions_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            })
        });

        $('[data-table="files"]').one('click', function (e) {

            $('#files-table').DataTable().clear().destroy();

            let table_table = $('#files-table').DataTable({
                initComplete: function () {
                    this.api().columns([1]).every(function () {
                        var column = this;
                        var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                    $(this).val()
                                );

                                column
                                    .search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                            });

                        column.data().unique().sort().each(function (d, j) {
                            select.append('<option value="' + d + '">' + d + '</option>');
                            $('select').selectpicker('refresh');
                        });
                    });
                },
                responsive: true,
                fixedHeader: {
                    header: true,
                    footer: true
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('project_files.index',$project) }}",
                    method: "post"
                },

                columns: [


                    {
                        data: 'file_title',
                        name: 'file_title'
                    },
                    {
                        data: 'file_description',
                        name: 'file_description',

                    },
                    {
                        data: 'created_at',
                        name: 'created_at',

                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false
                    }
                ],


                "order": [],
                'language': {
                    'lengthMenu': '_MENU_ {{__("records per page")}}',
                    "info": '{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)',
                    "search": '{{trans("file.Search")}}',
                    'paginate': {
                        'previous': '{{trans("file.Previous")}}',
                        'next': '{{trans("file.Next")}}'
                    }
                },
                'columnDefs': [
                    {
                        "orderable": false,
                        'targets': [0, 2],
                    },
                ],

                'select': {style: 'multi', selector: 'td:first-child'},
                'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            });
            new $.fn.dataTable.FixedHeader(table_table);
        });

        $('#files_form').on('submit', function (event) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('project_files.store',$project) }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function (data) {
                    let html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (let count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                        $('#files_form')[0].reset();
                        $('#files-table').DataTable().ajax.reload();
                    }
                    $('#files_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            })
        });

        $('#note_form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('project_notes.store',$project) }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function (data) {
                    let html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (let count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                    }
                    $('#note_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            })
        });

        $(document).on('click', '.delete-discussion', function () {

            if (confirm('{{__('Delete Selection',['key'=>trans('file.Discussions')])}}')) {

                let delete_id = $(this).attr('id');
                let target = "{{ route('projects.index') }}/" + delete_id + '/delete_discussions';
                $.ajax({
                    url: target,
                    success: function (data) {
                        let html = '';
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                        }
                        if (data.error) {
                            html = '<div class="alert alert-danger">' + data.error + '</div>';
                        }
                        setTimeout(function () {
                            $('#general_result').html(html).slideDown(300).delay(5000).slideUp(300);
                            $('#confirmModal').modal('hide');
                            $('#discussions-table').DataTable().ajax.reload();
                        }, 2000);
                    }
                })
            }

        });


        $(document).one('click', '.delete-file', function () {

            if (confirm('{{__('Delete Selection',['key'=>trans('file.Files')])}}')) {

                let delete_id = $(this).attr('id');
                let target = "{{ route('projects.index') }}/" + delete_id + '/delete_files';
                $.ajax({
                    url: target,
                    success: function (data) {
                        let html = '';
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                        }
                        if (data.error) {
                            html = '<div class="alert alert-danger">' + data.error + '</div>';
                        }
                        setTimeout(function () {
                            $('#general_result').html(html).slideDown(300).delay(5000).slideUp(300);
                            $('#confirmModal').modal('hide');
                            $('#files-table').DataTable().ajax.reload();
                        }, 2000);
                    }
                })
            }

        });

        $('.dynamic').change(function () {
            if ($(this).val() !== '') {
                let value = $(this).val();
                let first_name = $(this).data('first_name');
                let last_name = $(this).data('last_name');
                let _token = $('input[name="_token"]').val();
                $.ajax({
                    url: "{{ route('dynamic_employee') }}",
                    method: "POST",
                    data: {value: value, _token: _token, first_name: first_name, last_name: last_name},
                    success: function (result) {
                        $('select').selectpicker("destroy");
                        $('#employee_id').html(result);
                        $('select').selectpicker();

                    }
                });
            }
        });
    })(jQuery);

</script>
@endpush
