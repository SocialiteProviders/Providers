<?php

namespace SocialiteProviders\WeChatWeb;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'WECHAT_WEB';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['snsapi_login'];

    private $openId;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://open.weixin.qq.com/connect/qrconnect', $state);
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
        $response = $this->getHttpClient()->get('https://api.weixin.qq.com/sns/userinfo', [
            RequestOptions::QUERY => [
                'access_token' => $token, // HACK: Tencent use token in Query String, not in Header Authorization
                'openid'       => $this->credentialsResponseBody['openid'] ?? $this->openId, // HACK: Tencent need id
                'lang'         => 'zh_CN',
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
            // HACK: use unionid as user id
            'id'       => in_array('unionid', $this->getScopes(), true) ? $user['unionid'] : $user['openid'],
            // HACK: Tencent scope snsapi_base only return openid
            'nickname' => $user['nickname'] ?? null,
            'name'     => null,
            'email'    => null,
            'avatar'   => $user['headimgurl'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'appid'      => $this->clientId,
            'secret'     => $this->clientSecret,
            'code'       => $code,
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

    /**
     * {@inheritdoc}
     */
    protected function formatScopes(array $scopes, $scopeSeparator)
    {
        // HACK: unionid is a faker scope for user id
        if (in_array('unionid', $scopes, true)) {
            unset($scopes[array_search('unionid', $scopes, true)]);
        }

        return implode($scopeSeparator, $scopes);
    }

    public function setOpenId($openId)
    {
        $this->openId = $openId;

        return $this;
    }
}
