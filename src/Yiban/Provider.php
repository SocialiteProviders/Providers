<?php

namespace SocialiteProviders\Yiban;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'YIBAN';

    /**
     * @var string
     */
    protected $openId;

    /**
     * {@inheritdoc}.
     */
    protected $scopes = ['snsapi_userinfo'];

    /**
     * set Open Id.
     *
     * @param  string  $openId
     */
    public function setOpenId($openId)
    {
        $this->openId = $openId;
    }

    /**
     * 取消易班授权.
     */
    public function RevokeToken($token)
    {
        $response = $this->getHttpClient()->post($this->getRevokeUrl(), [
            RequestOptions::HEADERS     => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => ['client_id' => $this->clientId, 'access_token' => $token],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * 易班授权取消链接.
     */
    protected function getRevokeUrl()
    {
        return 'https://openapi.yiban.cn/oauth/revoke_token';
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://openapi.yiban.cn/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://openapi.yiban.cn/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://openapi.yiban.cn/user/real_me', [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::QUERY => [
                'access_token' => $token,
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
            'id'        => $user['info']['yb_userid'],
            'name'      => $user['info']['yb_username'],
            'sex'       => $user['info']['yb_sex'],
            'avatar'    => $user['info']['yb_userhead'],
            'schoolId'  => $user['info']['yb_schoolid'],
            'studentId' => $user['info']['yb_studentid'],
        ]);
    }
}
