<?php

namespace SocialiteProviders\Trello;

use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TRELLO';

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id'       => $user['extra']['id'],
            'nickname' => $user['nickname'],
            'name'     => $user['extra']['fullName'],
            'email'    => $user['extra']['email'],
            'avatar'   => $user['extra']['uploadedAvatarUrl'],
        ]);
    }
}
