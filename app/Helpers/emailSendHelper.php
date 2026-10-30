<?php

use App\Mail\AdminNotificationMail;
use App\Mail\CustomerWelcomeMail;
use App\Mail\EstimateViewMail;
use App\Mail\FollowUpAssignedMail;
use App\Mail\LeadAssignedMail;
use App\Mail\MeetingCustomerMail;
use App\Mail\MeetingStaffMail;
use App\Mail\ProjectCompletedMail;
use App\Mail\StaffCreatedMail;
use App\Mail\TaskAssignedMail;
use App\Mail\TicketCreatedMail;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// ─────────────────────────────────────────────────────────────────────────────
// MAIL CONFIGURATION
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('setMailConfig')) {
    /**
     * Dynamically set mail configuration from the settings table.
     * Only overrides if settings value is non-null and non-empty.
     * Falls back to .env values if settings are missing/null.
     */
    function setMailConfig(): void
    {
        try {
            $settings = DB::table('settings')->pluck('value', 'key')->toArray();

            // Only apply DB settings if mail_host is set AND mail_username is non-null
            $host     = $settings['mail_host']         ?? null;
            $username = $settings['mail_username']      ?? null;
            $password = $settings['mail_password']      ?? null;
            // If mail_from_address is missing (UI might not have it), use username as fallback
            $from = $settings['mail_from_address'] ?? $username;

            if ($host && $username && $password && $from) {
                Config::set('mail.mailers.smtp.host',       $host);
                Config::set('mail.mailers.smtp.port',       $settings['mail_port']       ?? 587);
                Config::set('mail.mailers.smtp.username',   $username);
                Config::set('mail.mailers.smtp.password',   $password);
                Config::set('mail.mailers.smtp.encryption', $settings['mail_encryption'] ?? 'tls');
                Config::set('mail.from.address',            $from);
                Config::set('mail.from.name',               $settings['mail_from_name']  ?? config('app.name'));

                // Force Laravel to rebuild the mailer with the new config
                app('mail.manager')->purge('smtp');
            }
            // else: use .env defaults (MAIL_HOST, MAIL_USERNAME etc.)
        } catch (\Throwable $e) {
            Log::error('setMailConfig failed: ' . $e->getMessage());
        }
    }
}

if (! function_exists('isMailConfigured')) {
    /**
     * Check if mail is properly configured (either via DB settings or .env).
     * Returns false if essential SMTP credentials are missing.
     */
    function isMailConfigured(): bool
    {
        // After setMailConfig() runs, check what's actually set in config
        $username = config('mail.mailers.smtp.username');
        $fromAddr = config('mail.from.address');
        $host     = config('mail.mailers.smtp.host');

        // These defaults mean "not configured"
        $invalidHosts    = ['mailpit', 'localhost', '127.0.0.1', null, ''];
        $invalidFromAddr = ['hello@example.com', null, ''];

        if (in_array($host, $invalidHosts, true)) {
            return false;
        }
        if (in_array($fromAddr, $invalidFromAddr, true)) {
            return false;
        }
        if (empty($username) || $username === 'null') {
            return false;
        }

        return true;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: Get Admin Emails
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('get_admin_emails')) {
    /**
     * Get all admin user email addresses (admin / super-admin roles).
     *
     * @return array<string>
     */
    function get_admin_emails(): array
    {
        try {
            return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin']))
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->pluck('email')
                ->filter()
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            Log::error('get_admin_emails failed: ' . $e->getMessage());
            return [];
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: Safe Email Dispatch (never breaks main flow)
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('safe_dispatch_mail')) {
    /**
     * Safely queue a mailable to one or more email addresses.
     * Silently skips if email is empty/invalid. Never throws.
     *
     * @param string|array<string> $emails
     * @param \Illuminate\Mail\Mailable $mailable
     */
    function safe_dispatch_mail($emails, \Illuminate\Mail\Mailable $mailable): void
    {
        try {
            setMailConfig();

            // Guard: skip if mail is not configured (missing SMTP credentials)
            if (! isMailConfigured()) {
                Log::warning('safe_dispatch_mail: Mail not configured — skipping ' . get_class($mailable) . '. Configure SMTP in CRM Settings or .env', [
                    'host'     => config('mail.mailers.smtp.host'),
                    'username' => config('mail.mailers.smtp.username'),
                    'from'     => config('mail.from.address'),
                ]);
                return;
            }

            $emails = is_array($emails) ? $emails : [$emails];

            // Filter out empty or invalid emails silently
            $validEmails = array_filter($emails, fn ($email) =>
                !empty($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL)
            );

            if (empty($validEmails)) {
                Log::debug('safe_dispatch_mail: No valid email address — skipping ' . get_class($mailable));
                return; // Skip silently — no valid email
            }

            dispatch(function () use ($validEmails, $mailable) {
                try {
                    setMailConfig(); // Re-apply config inside the background process
                    Mail::to(array_values($validEmails))->send($mailable);
                } catch (\Throwable $e) {
                    Log::error('Async email send failed: ' . $e->getMessage());
                }
            })->afterResponse();

            Log::info('safe_dispatch_mail: Dispatched ' . get_class($mailable), [
                'to' => array_values($validEmails),
            ]);

        } catch (\Throwable $e) {
            Log::error('safe_dispatch_mail failed: ' . $e->getMessage(), [
                'mailable' => get_class($mailable),
                'trace'    => $e->getTraceAsString(),
            ]);
            // Never rethrow — email failure must not break the main response
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Staff Created Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_staff_created_notification')) {
    /**
     * Send welcome email with credentials to newly created staff member.
     *
     * @param User   $user          The newly created staff user
     * @param string $plainPassword The plain-text password before hashing
     */
    function send_staff_created_notification(User $user, string $plainPassword): void
    {
        if (empty($user->email)) {
            return;
        }

        safe_dispatch_mail($user->email, new StaffCreatedMail($user, $plainPassword));
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. Lead Assigned Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_lead_assigned_notification')) {
    /**
     * Send email to the assigned staff when a lead is assigned/reassigned.
     *
     * @param Lead $lead
     */
    function send_lead_assigned_notification(Lead $lead): void
    {
        try {
            $lead->loadMissing(['assignedUser', 'leadSource', 'creator']);

            $staffEmail = $lead->assignedUser?->email;
            if (empty($staffEmail)) {
                return;
            }

            safe_dispatch_mail($staffEmail, new LeadAssignedMail($lead));

        } catch (\Throwable $e) {
            Log::error('send_lead_assigned_notification failed: ' . $e->getMessage());
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2.5. Task Assigned Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_task_assigned_notification')) {
    /**
     * Send email to the assigned staff when a task is assigned.
     *
     * @param Task $task
     */
    function send_task_assigned_notification(Task $task): void
    {
        try {
            $task->loadMissing(['assignedUser']);

            $staffEmail = $task->assignedUser?->email;
            if (empty($staffEmail)) {
                return;
            }

            safe_dispatch_mail($staffEmail, new TaskAssignedMail($task));

        } catch (\Throwable $e) {
            Log::error('send_task_assigned_notification failed: ' . $e->getMessage());
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2.6. Follow-Up Assigned Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_followup_assigned_notification')) {
    /**
     * Send email to the assigned staff when a follow-up is assigned.
     *
     * @param FollowUp $followUp
     */
    function send_followup_assigned_notification(FollowUp $followUp): void
    {
        try {
            $followUp->loadMissing(['assignedUser', 'lead']);

            $staffEmail = $followUp->assignedUser?->email;
            if (empty($staffEmail)) {
                return;
            }

            safe_dispatch_mail($staffEmail, new FollowUpAssignedMail($followUp));

        } catch (\Throwable $e) {
            Log::error('send_followup_assigned_notification failed: ' . $e->getMessage());
        }
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// 3. Customer Welcome Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_customer_welcome_notification')) {
    /**
     * Send welcome email to a newly created customer.
     *
     * @param Customer $customer
     */
    function send_customer_welcome_notification(Customer $customer): void
    {
        if (empty($customer->email)) {
            return; // Skip silently
        }

        safe_dispatch_mail($customer->email, new CustomerWelcomeMail($customer));
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. Admin Notification (Staff Activity)
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_admin_notification')) {
    /**
     * Send admin notification when a staff member creates/updates a record.
     * Only sends if the current actor is NOT an admin.
     *
     * @param string      $module    Module name (e.g. 'Customer', 'Lead', 'BOM')
     * @param string      $action    Action label (e.g. 'Created', 'Updated')
     * @param array       $details   Key-value pairs to show in email
     * @param string|null $entityUrl URL to view the record
     */
    function send_admin_notification(
        string  $module,
        string  $action,
        string  $recordName = 'N/A',
        array   $details = [],
        ?string $entityUrl = null
    ): void {
        try {
            $actor = auth('sanctum')->user() ?? auth()->user();

            // Only send if performed by staff (not admin)
            if (!$actor || $actor->isAdmin()) {
                return;
            }

            $adminEmails = get_admin_emails();
            if (empty($adminEmails)) {
                return;
            }

            $mailable = new AdminNotificationMail($module, $action, $actor, $recordName, $details, $entityUrl);
            safe_dispatch_mail($adminEmails, $mailable);

        } catch (\Throwable $e) {
            Log::error('send_admin_notification failed: ' . $e->getMessage());
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. Meeting Staff Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_meeting_staff_notification')) {
    /**
     * Send meeting reminder email to assigned staff.
     *
     * @param Meeting $meeting
     */
    function send_meeting_staff_notification(Meeting $meeting): void
    {
        try {
            $meeting->loadMissing(['assignedUser', 'customer']);

            $staffEmail = $meeting->assignedUser?->email;
            if (empty($staffEmail)) {
                return;
            }

            safe_dispatch_mail($staffEmail, new MeetingStaffMail($meeting));

        } catch (\Throwable $e) {
            Log::error('send_meeting_staff_notification failed: ' . $e->getMessage());
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. Meeting Customer Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_meeting_customer_notification')) {
    /**
     * Send meeting reminder email to the customer.
     *
     * @param Meeting $meeting
     */
    function send_meeting_customer_notification(Meeting $meeting): void
    {
        try {
            $meeting->loadMissing(['customer', 'assignedUser']);

            $customerEmail = $meeting->customer?->email;
            if (empty($customerEmail)) {
                return; // Skip silently — no email on customer
            }

            safe_dispatch_mail($customerEmail, new MeetingCustomerMail($meeting));

        } catch (\Throwable $e) {
            Log::error('send_meeting_customer_notification failed: ' . $e->getMessage());
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 7. Estimate Customer Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_estimate_view_notification')) {
    /**
     * Send estimate-ready email to customer with a "View Estimate" link.
     *
     * @param Estimate $estimate
     */
    function send_estimate_view_notification(Estimate $estimate): void
    {
        try {
            $estimate->loadMissing(['customer', 'creator']);

            $customerEmail = $estimate->customer?->email;
            if (empty($customerEmail)) {
                return; // Skip silently
            }

            safe_dispatch_mail($customerEmail, new EstimateViewMail($estimate));

        } catch (\Throwable $e) {
            Log::error('send_estimate_view_notification failed: ' . $e->getMessage());
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 8. Project Completed Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_project_completed_notification')) {
    /**
     * Send project completion thank-you email to customer.
     *
     * @param Project $project
     */
    function send_project_completed_notification(Project $project): void
    {
        try {
            $project->loadMissing(['customer']);

            $customerEmail = $project->customer?->email;
            if (empty($customerEmail)) {
                return; // Skip silently
            }

            safe_dispatch_mail($customerEmail, new ProjectCompletedMail($project));

        } catch (\Throwable $e) {
            Log::error('send_project_completed_notification failed: ' . $e->getMessage());
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// LEGACY: Support Ticket Created Notification
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('send_ticket_created_notification')) {
    /**
     * Send a created ticket notification email to the ticket customer.
     *
     * @param SupportTicket $ticket
     * @return bool
     */
    function send_ticket_created_notification(SupportTicket $ticket): bool
    {
        if (! $ticket->customer || empty($ticket->customer->email)) {
            return false;
        }

        try {
            dispatch(function () use ($ticket) {
                try {
                    setMailConfig();
                    Mail::to($ticket->customer->email)->send(new TicketCreatedMail($ticket));
                } catch (\Exception $e) {
                    Log::error('Async ticket created notification failed: ' . $e->getMessage());
                }
            })->afterResponse();
            return true;
        } catch (\Exception $e) {
            Log::error('send_ticket_created_notification failed: ' . $e->getMessage());
            return false;
        }
    }
}
