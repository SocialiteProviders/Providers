<?php

namespace SocialiteProviders\DingTalk;

use Illuminate\Support\Arr;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @link https://open.dingtalk.com/document/orgapp/sso-overview
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'DINGTALK';

    /**
     * {@inheritdoc}.
     */
    protected $scopes = ['openid'];

    /**
     * {@inheritdoc}.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://login.dingtalk.com/oauth2/auth', $state);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);
        $fields['prompt'] = 'consent';

        return $fields;
    }

    /**
     * {@inheritdoc}.
     */
    protected function getCode()
    {
        return $this->request->get('autCode');
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenUrl()
    {
        return 'https://api.dingtalk.com/v1.0/oauth2/userAccessToken';
    }

    /**
     * {@inheritdoc}.
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.dingtalk.com/v1.0/contact/users/me', [
            RequestOptions::HEADERS => [
                'x-acs-dingtalk-access-token' => $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}.
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['openId'],
            'unionid'  => $user['unionId'] ?? null,
            'nickname' => $user['nick'] ?? null,
            'avatar'   => $user['avatarUrl'] ?? null,
            'name'     => null,
            'email'    => $user['email'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenFields($code)
    {
        return [
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'code'  => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * {@inheritdoc}.
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::JSON => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}.
     */
    protected function parseAccessToken($body)
    {
        return Arr::get($body, 'accessToken');
    }

    /**
     * {@inheritdoc}.
     */
    protected function parseRefreshToken($body)
    {
        return Arr::get($body, 'refreshToken');
    }

    /**
     * {@inheritdoc}.
     */
    protected function parseExpiresIn($body)
    {
        return Arr::get($body, 'expireIn');
    }
}
