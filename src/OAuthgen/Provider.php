<?php

namespace SocialiteProviders\OAuthgen;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'OAUTHGEN';

    protected $scopeSeparator = ' ';

    /**
     * BASE_URL.
     */
    protected $oauthUrl = 'https://auth.oauthgen.com';

    protected $graphUrl = 'https://graph.oauthgen.com/api/v1';

    protected $scopes = ['blaze.graph.me', 'noconsent'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->oauthUrl.'/oauth2/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->oauthUrl.'/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->graphUrl.'/me', [
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
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar'] ?? null,
        ]);
    }
}
