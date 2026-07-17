<?php

namespace App\Listeners;

use App\Services\FirebaseNotificationService;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mirrors every in-app (database channel) notification to a Firebase push.
 *
 * This keeps the mobile app's system-tray notifications in sync with the
 * portal's in-app notifications automatically: any module that notifies a
 * user via the `database` channel (leave, WFH, announcements, warnings,
 * documents, tickets, projects, etc.) will also trigger a push — without
 * having to touch each individual Notification class.
 *
 * A device only receives a push when the target user has an `fcm_token`
 * saved (i.e. they logged in from the mobile app).
 */
class SendPushForDatabaseNotification
{
    public function __construct(private readonly FirebaseNotificationService $fcm)
    {
    }

    public function handle(NotificationSent $event): void
    {
        // Only mirror the in-app database notifications (avoid double-firing on mail/other channels).
        if ($event->channel !== 'database') {
            return;
        }

        $notifiable = $event->notifiable;

        $token = $this->resolveToken($notifiable);
        if ($token === '') {
            return;
        }

        try {
            $payload = $this->extractPayload($event);

            $title = $this->resolveTitle($event, $notifiable, $payload);
            $body = $this->resolveBody($payload);

            if ($body === '') {
                return;
            }

            $data = [
                'notification_type' => class_basename((string) ($event->notification::class ?? '')),
            ];

            if (! empty($payload['link'])) {
                $data['link'] = (string) $payload['link'];
            }

            $databaseId = $this->resolveDatabaseId($event);
            if ($databaseId !== null) {
                $data['notification_id'] = $databaseId;
            }

            $this->fcm->sendToToken($token, $title, $body, $data);
        } catch (Throwable $e) {
            // Never let a push failure break the underlying notification flow.
            Log::warning('Push mirror for database notification failed: '.$e->getMessage());
        }
    }

    private function resolveToken(mixed $notifiable): string
    {
        if (is_object($notifiable) && isset($notifiable->fcm_token)) {
            return trim((string) $notifiable->fcm_token);
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPayload(NotificationSent $event): array
    {
        // The database channel returns the stored DatabaseNotification model.
        $data = is_object($event->response) && isset($event->response->data)
            ? $event->response->data
            : null;

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (! is_array($data) && method_exists($event->notification, 'toArray')) {
            $data = $event->notification->toArray($event->notifiable);
        }

        return is_array($data) ? $data : [];
    }

    private function resolveDatabaseId(NotificationSent $event): ?string
    {
        if (is_object($event->response) && isset($event->response->id)) {
            return (string) $event->response->id;
        }

        return null;
    }

    /**
     * Prefer a title the notification defines itself, otherwise derive one
     * from the notification class name, otherwise fall back to the app name.
     *
     * @param  array<string, mixed>  $payload
     */
    private function resolveTitle(NotificationSent $event, mixed $notifiable, array $payload): string
    {
        if (method_exists($event->notification, 'pushTitle')) {
            $title = trim((string) $event->notification->pushTitle($notifiable));
            if ($title !== '') {
                return $title;
            }
        }

        if (! empty($payload['title'])) {
            return (string) $payload['title'];
        }

        $typeShort = class_basename((string) ($event->notification::class ?? ''));

        return $this->titleForType($typeShort);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveBody(array $payload): string
    {
        $body = (string) ($payload['data'] ?? $payload['message'] ?? $payload['body'] ?? '');

        return trim(strip_tags($body));
    }

    private function titleForType(string $typeShort): string
    {
        $map = [
            'LeaveRequestNotification' => 'Leave Update',
            'LeaveNotification' => 'Leave Update',
            'LeaveEventNotification' => 'Leave Update',
            'LeaveNotificationToAdmin' => 'Leave Request',
            'EmployeeLeaveNotification' => 'Leave Update',
            'WfhRequestNotificationToApprover' => 'WFH Update',
            'WfhEventNotification' => 'WFH Update',
            'AnnouncementPublished' => 'Announcement',
            'CompanyPolicyNotify' => 'Company Policy',
            'MeetingNotify' => 'Meeting',
            'EventNotify' => 'Event',
            'EmployeeWarningNotify' => 'Warning',
            'EmployeeAwardNotify' => 'Award',
            'EmployeePromotion' => 'Promotion',
            'EmployeeTransferNotify' => 'Transfer',
            'EmployeeTravelStatus' => 'Travel',
            'EmployeeTerminationNotify' => 'Termination',
            'EmployeeResignationNotify' => 'Resignation',
            'ComplaintFromNotify' => 'Complaint',
            'ComplainAgainstNotify' => 'Complaint',
            'TicketCreatedNotification' => 'Support Ticket',
            'TicketUpdatedNotification' => 'Support Ticket',
            'TicketAssignedNotification' => 'Support Ticket',
            'ProjectCreatedNotifiaction' => 'Project',
            'ProjectUpdatedNotification' => 'Project',
            'DocumentExpiry' => 'Document Expiry',
            'OfficialDocumentExpiry' => 'Document Expiry',
        ];

        return $map[$typeShort] ?? (string) config('app.name', 'HRMS');
    }
}
