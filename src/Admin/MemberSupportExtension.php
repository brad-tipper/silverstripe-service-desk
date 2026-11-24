<?php

namespace BradTipper\AppBackend\Admin;

use BradTipper\AppBackend\Auth\ApiMemberExtension;
use BradTipper\AppBackend\Model\ContactSubmission;
use BradTipper\AppBackend\Model\FeedbackSubmission;
use BradTipper\AppBackend\Model\Ticket;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Security\Member;

/**
 * What the service desk adds to a Member: a Feedback tab (feedback and contact messages they sent) in the CMS, and
 * their contact messages, feedback and tickets in the account data export.
 *
 * @extends Extension<Member>
 */
class MemberSupportExtension extends Extension
{
    public function updateCMSFields(FieldList $fields): void
    {
        $member = $this->owner;
        if (!$member->isInDB()) {
            return;
        }
        $fields->addFieldToTab('Root.Feedback', GridField::create(
            'FeedbackSent',
            'Feedback',
            FeedbackSubmission::get()->filter('MemberID', $member->ID)->sort('Created', 'DESC'),
            GridFieldConfig_RecordViewer::create(25)
        ));
        $fields->addFieldToTab('Root.Feedback', GridField::create(
            'ContactSent',
            'Contact messages',
            ContactSubmission::get()->filter('Email', $member->Email)->sort('Created', 'DESC'),
            GridFieldConfig_RecordViewer::create(25)
        ));
    }

    /** @param array<string, mixed> $data */
    public function updateAccountExport(array &$data): void
    {
        $member = $this->owner;
        $data['contactSubmissions'] = array_map($this->submissionRow(...), ContactSubmission::get()->filter('Email', $member->Email)->toArray());
        $data['feedbackSubmissions'] = array_map($this->submissionRow(...), FeedbackSubmission::get()->filter('MemberID', $member->ID)->toArray());
        $data['tickets'] = array_map(static fn (Ticket $t) => $t->toApiDetail(), Ticket::get()->filter('OwnerID', $member->ID)->toArray());
    }

    /** @return array<string, mixed> */
    private function submissionRow(ContactSubmission|FeedbackSubmission $row): array
    {
        return [
            'name' => $row->Name,
            'email' => $row->Email,
            'subject' => $row->Subject,
            'message' => $row->Message,
            'pageUrl' => $row->PageUrl,
            'createdAt' => ApiMemberExtension::iso($row->Created),
        ];
    }
}
