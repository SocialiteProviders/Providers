<?php

namespace SocialiteProviders\Tumblr;

use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'TUMBLR';

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id'       => null,
            'nickname' => $user['nickname'],
            'name'     => null,
            'email'    => null,
            'avatar'   => null,
        ]);
    }
}
