<?php

namespace SocialiteProviders\Goodreads;

use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'GOODREADS';

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id' => $user['id'],
            'nickname' => null,
            'name' => $user['name'],
            'email' => null,
            'avatar' => null,
        ]);
    }

    protected function hasNecessaryVerifier()
    {
        return $this->request->has('oauth_token');
    }
}
