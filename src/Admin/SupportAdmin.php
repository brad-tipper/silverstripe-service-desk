<?php

namespace BradTipper\RestfulFramework\Admin;

use BradTipper\RestfulFramework\Model\ContactSubmission;
use BradTipper\RestfulFramework\Model\FeedbackSubmission;
use SilverStripe\Admin\ModelAdmin;

/** Things people send in: contact messages and feedback. Other modules can add their own models to managed_models. */
class SupportAdmin extends ModelAdmin
{
    private static string $url_segment = 'support';

    private static string $menu_title = 'Support';

    private static string $menu_icon_class = 'font-icon-chat';

    private static $menu_priority = -0.3;

    private static array $managed_models = [
        ContactSubmission::class,
        FeedbackSubmission::class,
    ];
}
