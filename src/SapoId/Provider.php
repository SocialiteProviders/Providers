<?php

namespace SocialiteProviders\SapoId;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SAPOID';

    protected const ID_AUTH_URL = 'https://id.sapo.pt/oauth/v2/authorize';

    protected const ID_TOKEN_URL = 'https://id.sapo.pt/oauth/v2/token';

    protected const ID_USER_URL = 'https://id.sapo.pt/userinfo';

    protected $scopeSeparator = ' ';

    protected $scopes = ['openid'];

    protected $usesPKCE = true;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::ID_AUTH_URL, $state);
    }

    protected function getTokenUrl()
    {
        return self::ID_TOKEN_URL;
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(self::ID_USER_URL, [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        $responseUser['id'] = $user['sub'] ?? null;
        $responseUser['name'] = $user['given_name'] ?? null;
        $responseUser['email'] = $user['email'] ?? null;
        $responseUser['avatar'] = $user['picture'] ?? null;

        return (new User)->setRaw($responseUser)->map([
            'id'        => $responseUser['id'],
            'name'      => $responseUser['name'],
            'email'     => $responseUser['email'],
            'avatar'    => $responseUser['avatar'],
        ]);
    }
}
