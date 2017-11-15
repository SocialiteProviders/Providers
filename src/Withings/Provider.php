<?php

namespace SocialiteProviders\Withings;

use SocialiteProviders\Manager\OAuth1\User;
use SocialiteProviders\Manager\OAuth1\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'WITHINGS';

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id' => $user['uid'],
            'nickname' => null,
            'name' => $user['name'],
            'email' => null,
            'avatar' => null,
        ]);
    }
}
