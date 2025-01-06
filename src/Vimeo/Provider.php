<?php

namespace SocialiteProviders\Vimeo;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'VIMEO';

    protected $scopes = ['public'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.vimeo.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.vimeo.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.vimeo.com/me',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'bearer '.$token,
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
        return (new User)->setRaw($user)->map([
            'id'       => str_replace('/users/', null, $user['uri']),
            'nickname' => null, 'name' => $user['name'], 'email' => null,
            'avatar'   => null,
        ]);
    }
}
