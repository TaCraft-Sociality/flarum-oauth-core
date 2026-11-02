<?php

declare(strict_types=1);

namespace TacraftSociality\OauthCore;

use Flarum\Api\Context;
use Flarum\Api\Resource\UserResource;
use Flarum\Extend;
use Flarum\Foundation\ValidationException;
use Flarum\User\Event\Saving;
use Flarum\User\User;
use TacraftSociality\OauthCore\Providers\NotificationMailerProvider;
use TacraftSociality\OauthCore\Support\FakeEmail;
use TacraftSociality\OauthCore\Support\NotificationGuard;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    // Suffix used for generated addresses. Empty means the built-in, RFC 2606
    // reserved domain: never resolvable, never deliverable.
    (new Extend\Settings())
        ->default(FakeEmail::DOMAIN_SETTING, ''),

    // Notification mail is never sent: everything is delivered in-site only.
    (new Extend\ServiceProvider())->register(NotificationMailerProvider::class),

    // The virtual address an OAuth account logs in with is never shown to the
    // player who owns it; administrators still see it, so they can support
    // people and repair accounts.
    (new Extend\ApiResource(UserResource::class))
        ->field('email', function ($field) {
            return $field->visible(function (User $user, Context $context): bool {
                $actor = $context->getActor();

                if ($actor->can('editCredentials', $user)) {
                    return true;
                }

                return $actor->id === $user->id && ! FakeEmail::isFake($user->email);
            });
        }),

    (new Extend\Event())
        ->listen(Saving::class, function (Saving $event): void {
            $user = $event->user;
            $actor = $event->actor;
            $attributes = (array) ($event->data['attributes'] ?? []);
            $requestedEmail = $attributes['email'] ?? null;

            // The reserved domains belong to generated addresses only. Reject
            // them wherever a client (registration form, profile form, API)
            // tries to submit one; addresses assigned by an extension never
            // appear in the request body.
            if (is_string($requestedEmail) && $requestedEmail !== '' && ! $actor->isAdmin() && FakeEmail::isReserved($requestedEmail)) {
                throw new ValidationException([
                    'email' => '您不能使用系统保留的虚拟邮箱。',
                ]);
            }

            if (! $user->exists || $user->isDirty('preferences')) {
                NotificationGuard::disableEmailPreferences($user);
            }
        }),
];
