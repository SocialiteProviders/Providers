<?php

namespace SocialiteProviders\Crowdin;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'CROWDIN';

    protected $scopes = ['project.status'];

    /**
     * {@inheritdoc}
     */
    protected $consent = false;

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://accounts.crowdin.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://accounts.crowdin.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://crowdin.com/api/v2/user',
            [
                RequestOptions::HEADERS => [
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
        return (new User)->setRaw($user)->map([
            'id'       => $user['data']['id'],
            'nickname' => $user['data']['fullName'],
            'name'     => $user['data']['username'],
            'email'    => $user['data']['email'] ?? null,
            'avatar'   => $user['data']['avatarUrl'],
        ]);
    }
}
