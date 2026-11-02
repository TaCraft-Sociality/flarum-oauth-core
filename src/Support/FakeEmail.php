<?php

declare(strict_types=1);

namespace TacraftSociality\OauthCore\Support;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\LoginProvider;
use Flarum\User\User;

/**
 * Virtual email addresses for accounts that have no real one.
 *
 * OAuth sign-ups often carry no usable email address, and a player must not be
 * forced to hand one out. Such accounts get an address on a domain this site
 * controls instead. The address is derived from the provider identity, never
 * shown to the player and never leaves the server; it only exists so the
 * account satisfies the database and stays recognisable across sessions.
 *
 * The same formula is used by the skin site (Blessing Skin), so one player
 * resolves to one account wherever they log in.
 */
final class FakeEmail
{
    /**
     * Built-in domain. RFC 2606 reserved TLD: names ending in `.invalid` never
     * resolve and can never receive mail. Brand-free on purpose, so the
     * address says nothing about the site that created it.
     */
    public const LOCAL_DOMAIN = 'fake.invalid';

    /**
     * Domains written by older versions. Addresses on them are still
     * recognised as virtual (hidden from their owner, blocked for everyone),
     * otherwise upgrading would expose the addresses of existing accounts.
     * Nothing generates new addresses on them any more.
     */
    public const LEGACY_DOMAINS = [
        'example.invalid',
    ];

    /**
     * Admin setting that overrides the address suffix. Empty means "use
     * LOCAL_DOMAIN"; anything else is used verbatim (after normalisation).
     */
    public const DOMAIN_SETTING = 'tacraft-sociality-oauth-core.email_domain';

    /**
     * Local part prefix per provider. The prefix is only there to make the
     * address readable while debugging; identity comes from the hash.
     */
    public const PREFIXES = [
        'qq' => 'fakeqq_',
        'github' => 'fakegithub_',
        'microsoft' => 'fakemicrosoft_',
        'blessing' => 'fakeblessing_',
    ];

    /**
     * Every prefix this project has ever written, so older accounts are still
     * recognised as virtual.
     */
    public const LEGACY_PREFIXES = [
        'fakeqq_',
        'fakegithub_',
        'fakemicrosoft_',
        'fakeblessing_',
        'othersfake_',
        'qqfake_',
        'skinsfake_',
    ];

    /**
     * Providers that prove the player can receive mail.
     */
    public const STRONG_PROVIDERS = ['github', 'microsoft', 'google', 'discord', 'blessing'];

    public static function prefix(string $provider): string
    {
        return self::PREFIXES[$provider] ?? self::PREFIXES['qq'];
    }

    /**
     * Domain that newly generated addresses should use.
     *
     * Read from the admin setting; when it is empty (or not a usable domain)
     * the built-in reserved domain is used instead.
     */
    public static function domain(): string
    {
        return self::configuredDomain() ?? self::LOCAL_DOMAIN;
    }

    /**
     * The admin-configured domain, normalised, or null when unset/invalid.
     */
    private static function configuredDomain(): ?string
    {
        try {
            $value = resolve(SettingsRepositoryInterface::class)->get(self::DOMAIN_SETTING);
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        // Accept "example.com", "@example.com" and "example.com." alike.
        $domain = strtolower(trim($value));
        $domain = rtrim(ltrim($domain, '@'), '.');

        // A hostname with at least one dot; anything else would fail Flarum's
        // own email validation and break the account row.
        if (preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $domain) !== 1) {
            return null;
        }

        return $domain;
    }

    /**
     * Whether the given address belongs to the reserved virtual email pool.
     */
    public static function isFake(?string $email): bool
    {
        return self::isReserved($email) && self::startsWithPrefix($email);
    }

    /**
     * Whether the address is on one of the reserved virtual email domains,
     * whatever its local part.
     *
     * The whole domain is reserved, not just the virtual prefix: players must
     * not be able to register with, or switch to, any address on it.
     */
    public static function isReserved(?string $email): bool
    {
        if ($email === null) {
            return false;
        }

        $email = strtolower(trim($email));
        $at = strrpos($email, '@');

        if ($at === false) {
            return false;
        }

        $domain = rtrim(substr($email, $at + 1), '.');

        foreach (array_merge([self::domain()], self::LEGACY_DOMAINS) as $reserved) {
            if ($domain === $reserved || str_ends_with($domain, '.'.$reserved)) {
                return true;
            }
        }

        return false;
    }

    private static function startsWithPrefix(?string $email): bool
    {
        if ($email === null) {
            return false;
        }

        $email = strtolower(trim($email));

        foreach (self::LEGACY_PREFIXES as $prefix) {
            if (str_starts_with($email, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a fresh, unused virtual address.
     */
    public static function generate(string $provider = 'qq', ?string $domain = null): string
    {
        $prefix = self::prefix($provider);
        $domain ??= self::domain();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $email = $prefix.bin2hex(random_bytes(8)).'@'.$domain;

            if (! User::query()->where('email', $email)->exists()) {
                return $email;
            }
        }

        // Extremely unlikely collision: fall back to more entropy.
        return $prefix.bin2hex(random_bytes(16)).'@'.$domain;
    }

    /**
     * Derive the virtual address that belongs to a provider identity.
     *
     * The same identity always produces the same address, here and on any
     * other site that follows this rule (the skin site does). That is what
     * lets one player resolve to one account, whichever login button is used,
     * without either site exposing the address.
     */
    public static function forIdentity(string $provider, string $identifier, ?string $domain = null): string
    {
        $domain ??= self::domain();

        return self::prefix($provider).substr(hash('sha256', $provider.'|'.$identifier), 0, 16).'@'.$domain;
    }

    /**
     * Replace the domain of an address, keeping its local part.
     */
    public static function onDomain(string $email, string $domain): string
    {
        $at = strrpos($email, '@');

        return ($at === false ? $email : substr($email, 0, $at)).'@'.$domain;
    }

    /**
     * Take the preferred address when it is still free, otherwise fall back
     * to a random one so that saving can never hit the unique email index.
     */
    public static function assignUnique(string $preferred, string $provider = 'qq'): string
    {
        if (! User::query()->where('email', $preferred)->exists()) {
            return $preferred;
        }

        return self::generate($provider);
    }

    /**
     * Login providers currently bound to the account.
     *
     * @return string[]
     */
    public static function providers(User $user): array
    {
        if (! $user->exists) {
            return [];
        }

        return LoginProvider::query()
            ->where('user_id', $user->id)
            ->pluck('provider')
            ->all();
    }

    /**
     * Whether the account can log in through the given provider.
     */
    public static function hasBinding(User $user, string $provider): bool
    {
        return in_array($provider, self::providers($user), true);
    }

    /**
     * Whether the given provider is the only way to log in to the account.
     */
    public static function isOnlyProvider(User $user, string $provider): bool
    {
        $providers = self::providers($user);

        return in_array($provider, $providers, true)
            && count(array_intersect($providers, self::STRONG_PROVIDERS)) === 0;
    }

    /**
     * Whether the account can log in through QQ.
     */
    public static function hasQqBinding(User $user): bool
    {
        return self::hasBinding($user, 'qq');
    }

    /**
     * Whether the account can only log in through QQ, without a real email or
     * a password of its own.
     *
     * The original (database) address is used on purpose: this method is also
     * called from the Saving event, where $user->email already holds the new,
     * dirty value.
     */
    public static function isQqOnly(User $user): bool
    {
        $email = $user->exists ? (string) $user->getOriginal('email') : (string) $user->email;

        return self::isFake($email) && self::isOnlyProvider($user, 'qq');
    }
}