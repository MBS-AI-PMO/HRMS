<div class="row">
    <div class="table-responsive">
        <table id="employee_leave-table" class="table ">
            <thead>
            <tr>
                <th>{{__('Leave Type')}}</th>
                <th>{{trans('file.Department')}}</th>
                <th>{{trans('file.Duration')}}</th>
                <th>{{__('Applied Date')}}</th>
                <th>{{ __('Approved By') }}</th>
                <th class="not-exported">{{trans('file.action')}}</th>
            </tr>
            </thead>

        </table>
    </div>
</div>
<div class="modal fade hrms-leave-info-modal" id="leave_model" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="hrms-info-header">
                <div class="hrms-info-header-text">
                    <span class="hrms-info-eyebrow" id="leave_info_type_label">{{ __('Leave Type') }}</span>
                    <h4 class="modal-title" id="myModalLabel">{{__('Leave Info')}}</h4>
                </div>
                <button type="button" class="close hrms-info-close leave-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-0">
                <div class="hrms-info-hero">
                    <div class="hrms-info-person">
                        <div class="hrms-info-avatar" id="leave_info_avatar">—</div>
                        <div>
                            <h5 class="hrms-info-name" id="leave_employee_id_show">—</h5>
                            <p class="hrms-info-meta mb-0">
                                <span id="leave_department_id_show">—</span>
                                <span class="hrms-info-dot">·</span>
                                <span id="leave_company_id_show">—</span>
                            </p>
                        </div>
                    </div>
                    <div id="leave_status_id"></div>
                </div>

                <div class="hrms-info-section">
                    <h6 class="hrms-info-section-title">{{ __('Request Details') }}</h6>
                    <div class="hrms-info-grid">
                        <div class="hrms-info-card">
                            <span class="hrms-info-label">{{ __('Leave Type') }}</span>
                            <span class="hrms-info-value" id="leave_leave_type_id">—</span>
                        </div>
                        <div class="hrms-info-card">
                            <span class="hrms-info-label">{{ __('Total Days') }}</span>
                            <span class="hrms-info-value" id="leave_total_days_id">—</span>
                        </div>
                        <div class="hrms-info-card">
                            <span class="hrms-info-label">{{ __('Start Date') }}</span>
                            <span class="hrms-info-value" id="leave_start_date_id">—</span>
                        </div>
                        <div class="hrms-info-card">
                            <span class="hrms-info-label">{{ __('End Date') }}</span>
                            <span class="hrms-info-value" id="leave_end_date_id">—</span>
                        </div>
                        <div class="hrms-info-card">
                            <span class="hrms-info-label">{{ __('Applied Date') }}</span>
                            <span class="hrms-info-value" id="leave_applied_date_id">—</span>
                        </div>
                        <div class="hrms-info-card" id="leave_approved_by_row" style="display:none;">
                            <span class="hrms-info-label" id="leave_approved_by_label">{{ __('Approved By') }}</span>
                            <span class="hrms-info-value" id="leave_approved_by_id">—</span>
                        </div>
                    </div>
                </div>

                <div class="hrms-info-section">
                    <h6 class="hrms-info-section-title">{{ __('Leave Reason') }}</h6>
                    <p class="hrms-info-text-block" id="leave_leave_reason_id">—</p>
                </div>

                <div class="hrms-info-section" id="leave_remarks_section">
                    <h6 class="hrms-info-section-title">{{ trans('file.Remarks') }}</h6>
                    <p class="hrms-info-text-block" id="leave_remarks_id">—</p>
                </div>

                <div class="hrms-info-footer-meta">
                    <span class="hrms-info-chip"><i class="fa fa-adjust"></i> {{ __('Half Day') }}: <span id="leave_is_half_id">—</span></span>
                    <span class="hrms-info-chip"><i class="fa fa-bell"></i> {{ trans('file.Notification') }}: <span id="leave_is_notify_id">—</span></span>
                </div>
            </div>

            <div class="modal-footer hrms-info-modal-footer">
                <button type="button" class="btn hrms-info-close-btn" data-dismiss="modal">{{ trans('file.Close') }}</button>
            </div>
        </div>
    </div>
</div>
