<?php

namespace TacraftSociality\OauthCore\Support;

use Flarum\User\User;

/**
 * Turns the email channel of every notification off.
 *
 * Accounts that only have a virtual address cannot receive mail at all, and the
 * site delivers notifications in-site only, so the stored preferences are
 * forced to "off" for every user.
 */
final class NotificationGuard
{
    /**
     * Force every `notify_*_email` preference of the given user to false.
     *
     * The value read from the model already merges the registered defaults with
     * the stored JSON, so this also works for a user who never touched the
     * notification settings.
     */
    public static function disableEmailPreferences(User $user): void
    {
        $preferences = $user->preferences;

        if (! is_array($preferences)) {
            return;
        }

        $changed = false;

        foreach ($preferences as $key => $value) {
            if (! is_string($key) || $value === false) {
                continue;
            }

            if (str_starts_with($key, 'notify_') && str_ends_with($key, '_email')) {
                $preferences[$key] = false;
                $changed = true;
            }
        }

        if ($changed) {
            $user->preferences = $preferences;
        }
    }
}
