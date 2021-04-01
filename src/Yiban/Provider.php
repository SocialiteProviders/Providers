<?php

namespace SocialiteProviders\Yiban;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
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
     * @param string $openId
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
            'headers'     => ['Accept' => 'application/json'],
            'form_params' => ['client_id' => $this->clientId, 'access_token' => $token],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 易班授权取消链接.
     */
    protected function getRevokeUrl()
    {
        return 'https://openapi.yiban.cn/oauth/revoke_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://openapi.yiban.cn/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://openapi.yiban.cn/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userUrl = 'https://openapi.yiban.cn/user/real_me?access_token='.$token;
        $response = $this->getHttpClient()->get(
            $userUrl,
            $this->getRequestOptions()
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'        => $user['info']['yb_userid'],
            'name'      => $user['info']['yb_username'],
            'sex'       => $user['info']['yb_sex'],
            'avatar'    => $user['info']['yb_userhead'],
            'schoolId'  => $user['info']['yb_schoolid'],
            'studentId' => $user['info']['yb_studentid'],
        ]);
    }

    /**
     * Get the default options for an HTTP request.
     *
     * @return array
     */
    protected function getRequestOptions()
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
    }
}
