<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\leave;

class LeaveMailPresenter
{
    public static function build(leave $leave, string $event, string $actionUrl, bool $isWfh = false, bool $forEmployee = false): array
    {
        $leave->loadMissing('employee', 'LeaveType', 'company', 'department', 'approvedByUser');

        $general = GeneralSetting::query()->latest('id')->first();
        $company = $leave->company;
        $siteTitle = $general->site_title ?? config('app.name', 'HRMS');
        $companyName = $company->company_name ?? $siteTitle;

        $employeeName = optional($leave->employee)->full_name ?? __('Employee');
        $departmentName = optional($leave->department)->department_name ?? '—';
        $leaveTypeName = $isWfh
            ? __('Work From Home')
            : (optional($leave->LeaveType)->leave_type ?? __('Leave'));

        [$headline, $intro, $subject] = static::eventCopy($event, $isWfh, $forEmployee);
        $statusMeta = static::statusMeta($event);
        $decisionBy = in_array($event, ['approved', 'rejected'], true) ? $leave->approvedByName() : '';

        return [
            'subject' => $subject,
            'headline' => $headline,
            'intro' => $intro,
            'siteTitle' => $siteTitle,
            'companyName' => $companyName,
            'companyEmail' => $company->email ?? '',
            'companyWebsite' => $company->website ?? '',
            'logoUrl' => static::resolveLogoUrl(),
            'logoEmbed' => static::resolveLogoEmbed(),
            'employeeName' => $employeeName,
            'departmentName' => $departmentName,
            'leaveTypeName' => $leaveTypeName,
            'startDate' => $leave->start_date ?? '—',
            'endDate' => $leave->end_date ?? '—',
            'totalDays' => $leave->total_days ?? '—',
            'leaveReason' => trim((string) ($leave->leave_reason ?? '')),
            'remarks' => trim((string) ($leave->remarks ?? '')),
            'statusLabel' => $statusMeta['label'],
            'statusColor' => $statusMeta['color'],
            'statusBg' => $statusMeta['bg'],
            'decisionBy' => $decisionBy,
            'decisionLabel' => $event === 'approved'
                ? __('Approved By')
                : ($event === 'rejected' ? __('Rejected By') : ''),
            'actionUrl' => $actionUrl,
            'actionLabel' => __('View Request'),
            'isWfh' => $isWfh,
            'year' => date('Y'),
            'footerText' => trim((string) ($general->footer ?? '')),
        ];
    }

    protected static function eventCopy(string $event, bool $isWfh, bool $forEmployee = false): array
    {
        $type = $isWfh ? __('WFH request') : __('Leave request');

        switch ($event) {
            case 'requested':
                if ($forEmployee) {
                    return [
                        $isWfh ? __('Your WFH Request Has Been Submitted') : __('Your Leave Request Has Been Submitted'),
                        __('Your :type has been submitted successfully and is pending approval.', ['type' => strtolower($type)]),
                        $isWfh ? __('WFH request submitted') : __('Leave request submitted'),
                    ];
                }

                return [
                    $isWfh ? __('WFH Request Submitted') : __('Leave Request Submitted'),
                    __('A new :type has been submitted and requires your attention.', ['type' => strtolower($type)]),
                    $isWfh ? __('WFH request submitted') : __('Leave request submitted'),
                ];
            case 'approved':
                if ($forEmployee) {
                    return [
                        $isWfh ? __('Your WFH Request Has Been Approved') : __('Your Leave Request Has Been Approved'),
                        __('Your :type has been approved.', ['type' => strtolower($type)]),
                        $isWfh ? __('WFH request approved') : __('Leave request approved'),
                    ];
                }

                return [
                    $isWfh ? __('WFH Request Approved') : __('Leave Request Approved'),
                    __('The following :type has been approved.', ['type' => strtolower($type)]),
                    $isWfh ? __('WFH request approved') : __('Leave request approved'),
                ];
            case 'rejected':
                if ($forEmployee) {
                    return [
                        $isWfh ? __('Your WFH Request Has Been Rejected') : __('Your Leave Request Has Been Rejected'),
                        __('Your :type has been rejected.', ['type' => strtolower($type)]),
                        $isWfh ? __('WFH request rejected') : __('Leave request rejected'),
                    ];
                }

                return [
                    $isWfh ? __('WFH Request Rejected') : __('Leave Request Rejected'),
                    __('The following :type has been rejected.', ['type' => strtolower($type)]),
                    $isWfh ? __('WFH request rejected') : __('Leave request rejected'),
                ];
            default:
                return [
                    $isWfh ? __('WFH Request Updated') : __('Leave Request Updated'),
                    __('The following :type is pending review.', ['type' => strtolower($type)]),
                    $isWfh ? __('WFH request updated') : __('Leave request updated'),
                ];
        }
    }

    protected static function statusMeta(string $event): array
    {
        switch ($event) {
            case 'approved':
                return ['label' => __('Approved'), 'color' => '#166534', 'bg' => '#dcfce7'];
            case 'rejected':
                return ['label' => __('Rejected'), 'color' => '#991b1b', 'bg' => '#fee2e2'];
            case 'requested':
            case 'pending':
                return ['label' => __('Pending'), 'color' => '#92400e', 'bg' => '#fef3c7'];
            default:
                return ['label' => ucfirst($event), 'color' => '#374151', 'bg' => '#f3f4f6'];
        }
    }

    protected static function resolveLogoUrl(): ?string
    {
        if (! is_file(public_path('logo/logo.jpeg'))) {
            return null;
        }

        return asset('logo/logo.jpeg');
    }

    protected static function resolveLogoEmbed(): ?string
    {
        $logoPath = public_path('logo/logo.jpeg');

        if (! is_file($logoPath)) {
            return null;
        }

        $mime = @mime_content_type($logoPath) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($logoPath));
    }
}
