<?php

namespace SocialiteProviders\OpenIDConnect\Providers;

use InvalidArgumentException;
use SocialiteProviders\OpenIDConnect\Provider;

/**
 * Telegram Login. Telegram's `sub` is an opaque value; the numeric user id
 * that bots and Mini Apps know arrives as the `id` claim of the `profile`
 * scope, so that is what getId() returns.
 *
 * @see https://core.telegram.org/bots/telegram-login
 */
class TelegramProvider extends Provider
{
    // Telegram has no `email` scope.
    protected $scopes = ['openid', 'profile'];

    protected function configDefaults(array $config): array
    {
        return [
            'base_url' => 'https://oauth.telegram.org',
        ];
    }

    /**
     * `profile` is requested even when the scopes are narrowed: without it
     * the id_token carries no Telegram user id.
     */
    public function getScopes(): array
    {
        return array_values(array_unique([...parent::getScopes(), 'profile']));
    }

    /**
     * Telegram has no UserInfo endpoint (its discovery document advertises
     * none), and every claim is already in the id_token.
     */
    protected function getUserByToken($token)
    {
        return null;
    }

    protected function mapUserToObject(array $user)
    {
        $id = $user['id'] ?? null;

        if (! is_int($id) && ! (is_string($id) && preg_match('/^\d+$/', $id) === 1)) {
            throw new InvalidArgumentException('JWT: Missing the Telegram user id claim; the profile scope must be granted.', 401);
        }

        return parent::mapUserToObject($user)->map([
            'id'       => (string) $id,
            'nickname' => $user['preferred_username'] ?? null,
            'avatar'   => $user['picture'] ?? null,
        ]);
    }
}
