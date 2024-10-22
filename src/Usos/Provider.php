<?php

namespace SocialiteProviders\Usos;

use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'USOS';

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user['extra'])->map([
            'id'       => $user['id'],
            'nickname' => $user['nickname'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar'],
        ]);
    }

    public static function additionalConfigKeys()
    {
        return ['profile_fields_selector', 'domain'];
    }
}
