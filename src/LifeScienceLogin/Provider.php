<?php

namespace SocialiteProviders\LifeScienceLogin;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://lifescience-ri.eu/ls-login/documentation/service-provider-documentation/service-provider-documentation.html
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'LIFESCIENCELOGIN';

    /**
     * {@inheritdoc}
     */
    protected $usesPKCE = true;

    protected $scopeSeparator = ' ';

    protected $scopes = ['openid', 'email', 'profile'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://login.aai.lifescience-ri.eu/oidc/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://login.aai.lifescience-ri.eu/oidc/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://login.aai.lifescience-ri.eu/oidc/userinfo', [
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
            'id'          => $user['sub'],
            'name'        => $user['name'],
            'given_name'  => $user['given_name'],
            'family_name' => $user['family_name'],
            'email'       => $user['email'],
        ]);
    }
}
