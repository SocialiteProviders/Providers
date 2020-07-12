<?php

namespace SocialiteProviders\GarminConnect;

use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'GARMIN_CONNECT';

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if (!$this->hasNecessaryVerifier()) {
            throw new InvalidArgumentException('Invalid request. Missing OAuth verifier.');
        }

        $token = $this->getToken();
        if (is_array($token) && !is_null($token['tokenCredentials'])) {
            $token = $token['tokenCredentials'];
        }

        return (new User())->setToken($token->getIdentifier(), $token->getSecret());
    }
}
