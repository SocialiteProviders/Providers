<?php

namespace SocialiteProviders\WeChatServiceAccount;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'WECHAT_SERVICE_ACCOUNT';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['snsapi_userinfo'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://open.weixin.qq.com/connect/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.weixin.qq.com/sns/oauth2/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        // HACK: Tencent return id when grant token, and can not get user by this token
        if (in_array('snsapi_base', $this->scopes)) {
            return ['openid' => $this->credentialsResponseBody['openid']];
        }
        $response = $this->getHttpClient()->get('https://api.weixin.qq.com/sns/userinfo', [
            'query' => [
                'access_token' => $token, // HACK: Tencent use token in Query String, not in Header Authorization
                'openid'       => $this->credentialsResponseBody['openid'],
                'lang'         => 'zh_CN',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['openid'],
            'nickname' => $user['nickname'],
            'name'     => null,
            'email'    => null,
            'avatar'   => $user['headimgurl'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);
        unset($fields['client_id']);
        $fields['appid'] = $this->clientId; // HACK: Tencent use appid, not app_id or client_id

        return $fields;
    }
}
