<script>
    (function ($) {
        if (typeof window.hrmsFillLeaveInfoModal === 'function') {
            return;
        }

        function hrmsLeaveInitials(name) {
            name = (name || '').trim();
            if (!name) {
                return '—';
            }

            var parts = name.split(/\s+/).filter(Boolean);
            if (parts.length >= 2) {
                return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
            }

            return name.substring(0, 2).toUpperCase();
        }

        function hrmsLeaveStatusBadge(status) {
            status = (status || '').toLowerCase();
            var cls = 'hrms-badge-default';
            if (status === 'pending') {
                cls = 'hrms-badge-pending';
            } else if (status === 'approved') {
                cls = 'hrms-badge-approved';
            } else if (status === 'rejected') {
                cls = 'hrms-badge-rejected';
            }

            var label = status ? status.charAt(0).toUpperCase() + status.slice(1) : '—';
            return '<span class="hrms-status-badge ' + cls + '">' + label + '</span>';
        }

        window.hrmsFillLeaveInfoModal = function (result, selectors, labels) {
            selectors = selectors || {};
            labels = labels || {};
            var data = (result && result.data) ? result.data : {};

            function setText(selector, value) {
                if (!selector) {
                    return;
                }
                $(selector).text(value || '—');
            }

            if (selectors.avatar) {
                $(selectors.avatar).text(hrmsLeaveInitials(result.employee_name));
            }

            setText(selectors.employee, result.employee_name);
            setText(selectors.department, result.department);
            setText(selectors.company, result.company_name);
            setText(selectors.type, result.leave_type_name);
            setText(selectors.startDate, result.start_date_name || data.start_date);
            setText(selectors.endDate, result.end_date_name || data.end_date);
            setText(selectors.appliedDate, data.created_at);
            setText(selectors.totalDays, data.total_days);

            if (selectors.status) {
                $(selectors.status).html(hrmsLeaveStatusBadge(data.status));
            }

            setText(selectors.reason, data.leave_reason || '—');

            var remarks = (data.remarks || '').trim();
            if (selectors.remarksSection) {
                if (remarks) {
                    $(selectors.remarksSection).show();
                    setText(selectors.remarks, remarks);
                } else {
                    $(selectors.remarksSection).hide();
                }
            }

            var approvedBy = (result.approved_by_name || '').trim();
            var status = (data.status || '').toLowerCase();
            if (selectors.approvedByRow) {
                if (approvedBy && approvedBy !== '-' && status !== 'pending') {
                    $(selectors.approvedByRow).show();
                    if (selectors.approvedByLabel) {
                        $(selectors.approvedByLabel).text(
                            status === 'rejected'
                                ? (labels.rejectedBy || 'Rejected By')
                                : (labels.approvedBy || 'Approved By')
                        );
                    }
                    setText(selectors.approvedById, approvedBy);
                } else {
                    $(selectors.approvedByRow).hide();
                }
            }

            var yes = labels.yes || 'Yes';
            var no = labels.no || 'No';
            var on = labels.on || 'On';
            var off = labels.off || 'Off';

            if (selectors.halfDay) {
                setText(selectors.halfDay, parseInt(data.is_half, 10) === 1 ? yes : no);
            }

            if (selectors.notify) {
                setText(selectors.notify, parseInt(data.is_notify, 10) === 1 ? on : off);
            }
        };

        window.hrmsOpenLeaveInfoModal = function (modalSelector, title) {
            var $modal = $(modalSelector);
            if (!$modal.length) {
                return;
            }

            if (!$modal.parent().is('body')) {
                $modal.appendTo('body');
            }

            if (title) {
                $modal.find('.modal-title').first().text(title);
            }

            $modal.modal('show');
        };
    })(jQuery);
</script>
