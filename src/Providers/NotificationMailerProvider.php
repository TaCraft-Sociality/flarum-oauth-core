<?php

namespace TacraftSociality\OauthCore\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Notification\NotificationMailer;
use TacraftSociality\OauthCore\Support\GuardedNotificationMailer;

/**
 * Swaps the core notification mailer for the guarded one that refuses to send
 * any notification mail.
 */
class NotificationMailerProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->bind(NotificationMailer::class, GuardedNotificationMailer::class);
    }
}
