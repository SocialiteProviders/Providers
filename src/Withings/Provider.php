<?php

namespace SocialiteProviders\Withings;

use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'WITHINGS';

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id'       => $user['uid'],
            'nickname' => null,
            'name'     => $user['name'],
            'email'    => null,
            'avatar'   => null,
        ]);
    }
}
