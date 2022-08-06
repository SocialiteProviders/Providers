<?php

namespace SocialiteProviders\Figma;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FIGMA';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['file_read'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://www.figma.com/oauth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.figma.com/api/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.figma.com/v1/me', [
            RequestOptions::HEADERS => [
                'X-FIGMA-TOKEN' => $token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'email'    => $user['email'],
            'nickname' => $user['handle'],
            'name'     => $user['handle'],
            'avatar'   => $user['img_url'],
        ]);
    }
}
