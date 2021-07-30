<?php

namespace SocialiteProviders\TwentyThreeAndMe;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'TWENTYTHREEANDME';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://api.23andme.com/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.23andme.com/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.23andme.com/1/user/?email=true',
            [
                RequestOptions::HEADERS =>  [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'    => $user['id'], 'nickname' => null, 'name' => null,
            'email' => $user['email'], 'avatar' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
