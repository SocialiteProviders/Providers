<?php

namespace SocialiteProviders\JohnDeere;

use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'JOHN_DEERE';

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id' => $user['id'],
            'name' => $user['name'],
        ]);
    }
}
