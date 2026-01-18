<?php

namespace SocialiteProviders\OnlineScoutManager;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'ONLINESCOUTMANAGER';

    public const URL = 'https://www.onlinescoutmanager.co.uk';

    protected $scopes = [
        'section:badge:read',
        'section:event:read',
        'section:programme:read',
    ];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::URL.'/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return self::URL.'/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(self::URL.'/oauth/resource', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $user_data = $user['data'];

        return (new User)->setRaw($user)->map([
            'id'        => $user_data['user_id'],
            'name'      => $user_data['full_name'],
            'email'     => $user_data['email'],
            'avatar'    => $user_data['profile_picture_url'] ?? null,
            'is_leader' => $user_data['has_section_access'] ?? false
        ]);
    }
}
