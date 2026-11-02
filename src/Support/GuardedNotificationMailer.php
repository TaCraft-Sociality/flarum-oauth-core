<?php

namespace TacraftSociality\OauthCore\Support;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\NotificationMailer;
use Flarum\User\User;

/**
 * Last line of defence: notification emails are never sent.
 *
 * Notifications are delivered in-site only: the email column is hidden from the
 * notification grid and the stored preferences are forced off, but queued jobs
 * (and anything calling the mailer directly) still pass through here, so no
 * message can escape and no bounce can be produced.
 */
class GuardedNotificationMailer extends NotificationMailer
{
    public function send(MailableInterface&BlueprintInterface $blueprint, User $user): void
    {
        //
    }
}
