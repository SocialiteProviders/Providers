<?php

namespace SocialiteProviders\Flickr;

use SocialiteProviders\Manager\OAuth1\User;
use SocialiteProviders\Manager\OAuth1\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'FLICKR';

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id' => $user['id'],
            'nickname' => $user['nickname'],
            'name' => $user['name'],
            'email' => null,
            'avatar' => null,
        ]);
    }
}
