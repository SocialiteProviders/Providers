<?php

namespace SocialiteProviders\Flickr;

use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FLICKR';

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['perms'];
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user['extra'])->map([
            'id'       => $user['id'],
            'nickname' => $user['nickname'],
            'name'     => $user['name'],
            'email'    => null,
            'avatar'   => null,
        ]);
    }
}
