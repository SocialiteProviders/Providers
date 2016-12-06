<?php

namespace SocialiteProviders\Xing;

use SocialiteProviders\Manager\OAuth1\User;
use SocialiteProviders\Manager\OAuth1\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'XING';

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id' => $user['id'],
            'nickname' => null,
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => $user['avatar'],
        ]);
    }
}
