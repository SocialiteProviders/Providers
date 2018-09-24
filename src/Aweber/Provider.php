<?php

namespace App\SocialiteProviders\Aweber;

use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'AWEBER';

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id' => $user['id']
        ]);
    }

}