<?php

namespace SocialiteProviders\Trello;

use SocialiteProviders\Manager\OAuth1\User;
use SocialiteProviders\Manager\OAuth1\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'TRELLO';

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id' => null,
            'nickname' => $user['nickname'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => null,
        ]);
    }
}
