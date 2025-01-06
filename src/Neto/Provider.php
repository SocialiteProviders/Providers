<?php

namespace SocialiteProviders\Neto;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'NETO';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://apps.getneto.com/oauth/v2/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://apps.getneto.com/oauth/v2/token';
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::QUERY   => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        return $this->credentialsResponseBody;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $name = trim(($user['user']['firstName'] ?? '').' '.($user['user']['lastName'] ?? ''));

        return (new User)->setRaw($user)->map([
            'id'       => $user['user']['id'] ?? null,
            'nickname' => null,
            'name'     => ! empty($name) ? $name : null,
            'email'    => $user['user']['email'] ?? null,
            'avatar'   => null,
        ]);
    }
}
