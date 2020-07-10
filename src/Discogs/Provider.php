<?php

namespace SocialiteProviders\Discogs;

use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'DISCOGS';

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if (!$this->hasNecessaryVerifier()) {
            throw new InvalidArgumentException('Invalid request. Missing OAuth verifier.');
        }
        $token = $this->getToken();
        $tokenCredentials = $token['tokenCredentials'];
        $user = $this->server->getUserDetails($tokenCredentials);

        return (new User())->setRaw($user->extra)->map([
            'id'       => $user->id,
            'nickname' => $user->nickname,
            'name'     => $user->name,
            'email'    => $user->email,
            'avatar'   => $user->avatar,
        ])->setToken($tokenCredentials->getIdentifier(), $tokenCredentials->getSecret());
    }
}
