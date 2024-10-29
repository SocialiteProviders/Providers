<?php

namespace SocialiteProviders\Patreon;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PATREON';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'campaigns',
        'identity',
        'identity[email]',
    ];

    /**
     * {@inherticdoc}.
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.patreon.com/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.patreon.com/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.patreon.com/api/oauth2/v2/identity', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'fields[user]' => 'email,full_name,image_url,vanity',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $userData = $user['data'];
        $userAttributes = $userData['attributes'];

        return (new User)->setRaw($user)->map([
            'id'       => $userData['id'],
            'nickname' => Arr::get($userAttributes, 'vanity', $userAttributes['full_name']),
            'name'     => $userAttributes['full_name'],
            'email'    => $userAttributes['email'],
            'avatar'   => $userAttributes['image_url'],
        ]);
    }
}
