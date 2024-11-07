<?php

namespace SocialiteProviders\TwentyThreeAndMe;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TWENTYTHREEANDME';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.23andme.com/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.23andme.com/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.23andme.com/1/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'email' => 'true',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'    => $user['id'], 'nickname' => null, 'name' => null,
            'email' => $user['email'], 'avatar' => null,
        ]);
    }
}
