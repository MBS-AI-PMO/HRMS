<div id="viewTeamModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('View Team') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><i class="dripicons-cross"></i></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-sm mb-0">
                    <tbody>
                    <tr>
                        <th style="width:35%">{{ __('Team Name') }}</th>
                        <td id="view_team_name">-</td>
                    </tr>
                    <tr>
                        <th>{{ trans('file.Company') }}</th>
                        <td id="view_team_company">-</td>
                    </tr>
                    <tr>
                        <th>{{ trans('file.Department') }}</th>
                        <td id="view_team_department">-</td>
                    </tr>
                    <tr>
                        <th>{{ __('Department Heads') }}</th>
                        <td id="view_team_department_heads">-</td>
                    </tr>
                    <tr>
                        <th>{{ __('Project Manager') }}</th>
                        <td id="view_team_project_manager">-</td>
                    </tr>
                    <tr>
                        <th>{{ __('Assistant HR') }}</th>
                        <td id="view_team_assistant_hr">-</td>
                    </tr>
                    <tr>
                        <th>{{ __('Team Members') }}</th>
                        <td id="view_team_members">-</td>
                    </tr>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <td id="view_team_description">-</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('file.Close') }}</button>
            </div>
        </div>
    </div>
</div>
