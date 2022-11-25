<?php

namespace SocialiteProviders\Huawei;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use Laravel\Socialite\Two\User;
use GuzzleHttp\RequestOptions;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'HUAWEI';

    private const TOKEN_URL = 'https://oauth-login.cloud.huawei.com/oauth2/v3/token';
    private const AUTH_URL  = 'https://oauth-login.cloud.huawei.com/oauth2/v3/authorize';
    private const USER_URL  = 'https://account.cloud.huawei.com/rest.php';

    protected $encodingType = PHP_QUERY_RFC3986;

    protected $scopeSeparator = ' ';

    protected $parameters = [
        'response_mode' => 'form_post',
    ];

    protected $scopes = ['openid', 'profile', 'email'];

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(self::AUTH_URL, $state);
    }

    protected function getTokenUrl()
    {
        return self::TOKEN_URL;
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post(self::USER_URL, [
            RequestOptions::FORM_PARAMS => $this->userFields($token),
            RequestOptions::HEADERS => ['Content-Type' => 'application/x-www-form-urlencoded'],
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function userFields($token)
    {
        return [
            'access_token' => $token,
            'nsp_svc'      => 'GOpen.User.getInfo',
            'getNickName'  => 1,
        ];
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())
            ->setRaw($user)
            ->map([
                'id'       => $user['openID'] ?? null,
                'openid'   => $user['openID'] ?? null,
                'nickname' => $user['displayName'] ?? null,
                'avatar'   => $user['headPictureURL'] ?? null,
                'email'    => $user['email'] ?? null,
            ]);
    }
}
