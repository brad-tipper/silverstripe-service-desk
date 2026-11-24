<?php

namespace BradTipper\AppBackend\Support;

use BradTipper\AppBackend\AppConfig;
use BradTipper\AppBackend\Model\Ticket;
use BradTipper\AppBackend\Model\TicketMessage;
use SilverStripe\Security\Member;

/** The emails around support tickets. Each one is queued and none of them throws. */
final class TicketNotifications
{
    /** The reporter is told their ticket arrived, and the team is told there is something to read. */
    public static function ticketReceived(Member $member, Ticket $ticket): void
    {
        $label = strtolower($ticket->getTypeLabel());
        Notifications::to($member, "We got your {$label} (#{$ticket->ID})", [
            'heading' => "We got your {$label}",
            'paragraphs' => [
                "#{$ticket->ID}: {$ticket->Title}",
                'We read everything. You can follow progress and add notes any time in the app; we will email you when there is a reply or the status changes.',
            ],
            'button' => ['label' => 'View your ticket', 'url' => self::ticketUrl($ticket)],
        ]);
        self::toSupport($member, $ticket, "New {$label} (#{$ticket->ID}): {$ticket->Title}", $ticket->Description);
    }

    /** The user added a note to an existing ticket. */
    public static function ticketUserNote(Member $member, Ticket $ticket, string $body): void
    {
        self::toSupport($member, $ticket, "Reply on #{$ticket->ID}: {$ticket->Title}", $body);
    }

    /** The team replied (a note that is not internal). */
    public static function ticketReply(Member $member, Ticket $ticket, TicketMessage $message): void
    {
        Notifications::to($member, "Reply to your {$ticket->getTypeLabel()} (#{$ticket->ID})", [
            'heading' => "A reply to #{$ticket->ID}",
            'paragraphs' => array_merge([$ticket->Title], self::paragraphs((string) $message->Body)),
            'footnote' => 'Open the ticket in the app to reply.',
            'button' => ['label' => 'View and reply', 'url' => self::ticketUrl($ticket)],
        ]);
    }

    /** The team moved the ticket along. Moving it back to open says nothing. */
    public static function ticketStatus(Member $member, Ticket $ticket): void
    {
        $says = [
            'in_progress' => 'We are working on it.',
            'planned' => 'We have planned it, and will email you when it ships.',
            'resolved' => 'We have marked it resolved. If it is not, reply in the app and it will reopen.',
            'closed' => 'We have closed this ticket.',
        ];
        if (!isset($says[$ticket->Status])) {
            return;
        }
        Notifications::to($member, "#{$ticket->ID} is now {$ticket->getStatusLabel()}", [
            'heading' => "{$ticket->getTypeLabel()} #{$ticket->ID}: {$ticket->getStatusLabel()}",
            'paragraphs' => [$ticket->Title, $says[$ticket->Status]],
            'button' => ['label' => 'View your ticket', 'url' => self::ticketUrl($ticket)],
        ]);
    }

    private static function toSupport(Member $member, Ticket $ticket, string $subject, string $body): void
    {
        $support = EmailTemplate::supportEmail();
        if ($support === '' || Mailer::isReservedTestAddress((string) $member->Email)) {
            return;
        }
        try {
            Mailer::notify($support, $subject, [
                'heading' => $subject,
                'paragraphs' => array_merge(["From {$member->Email} (user {$member->ID}), status {$ticket->getStatusLabel()}."], self::paragraphs($body)),
                'button' => ['label' => 'Open in admin', 'url' => rtrim((string) AppConfig::get('public_url'), '/') . "/admin/tickets/BradTipper-AppBackend-Model-Ticket/EditForm/field/BradTipper-AppBackend-Model-Ticket/item/{$ticket->ID}/edit"],
            ]);
        } catch (\Throwable $e) {
            // Never fail the caller over a notification.
        }
    }

    /** @return string[] */
    private static function paragraphs(string $text): array
    {
        $parts = preg_split('/\R{2,}/', trim($text)) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn ($p) => $p !== ''));
    }

    private static function ticketUrl(Ticket $ticket): string
    {
        return Notifications::webUrl(rtrim((string) AppConfig::get('tickets_path'), '/') . '/' . $ticket->UUID);
    }
}
