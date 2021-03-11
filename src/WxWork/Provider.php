<?php

namespace SocialiteProviders\WeixinWork;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Wxwork provider class.
 *
 * @see https://open.work.weixin.qq.com/api/doc/90000/90135/91020
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'WEIXINWORK';

    protected $scopes = ['snsapi_base'];

    protected $userid = '';

    /**
     * {@inheritdoc}.
     */
    protected function getAuthUrl($state)
    {
        $isWxWork = Str::is('*wxwork*', request()->userAgent());

        if ($isWxWork) {
            //企业微信内部静默授权
            return $this->buildAuthUrlFromBase('https://open.weixin.qq.com/connect/oauth2/authorize', $state).'#wechat_redirect';
        } else {
            //非企业微信客户端 使用扫码登录
            return $this->buildAuthUrlFromBase('https://open.work.weixin.qq.com/wwopen/sso/qrConnect', $state);
        }
    }

    /**
     * {@inheritdoc}.
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        $query = http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);

        return $url.'?'.$query;
    }

    /**
     * {@inheritdoc}.
     */
    protected function getCodeFields($state = null)
    {
        return [
            'appid'         => $this->corpid,
            'agentid'       => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'state'         => $state,
        ];
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenUrl()
    {
        return 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo';
    }

    /**
     * {@inheritdoc}.
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://qyapi.weixin.qq.com/cgi-bin/user/get', [
            'query' => [
                'access_token' => $token,
                'userid'       => $this->userid,
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
            'id'        => Arr::get($user, 'userid'),
            'nickname'  => $user['alias'] ?? $user['name'],
            'name'      => $user['name'],
            'avatar'    => $user['avatar'],
            'email'     => $user['email'],
            'mobile'    => $user['mobile'],
            'gender'    => $user['gender'],
        ]);
    }

    /**
     * {@inheritdoc}.
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'query' => [
                'access_token'  => $this->getAccessToken()['access_token'],
                'code'          => $code,
            ],
        ]);
        $this->userid = json_decode($response->getBody(), true)['UserId'];
        $this->credentialsResponseBody = $this->getAccessToken();

        return $this->credentialsResponseBody;
    }

    /**
     * get access token.
     *
     * @return void
     */
    protected function getAccessToken()
    {
        $cache_key = 'WXWORK_'.$this->clientId;
        $access_token = Cache::remember($cache_key, 7200, function () {
            $response = $this->getHttpClient()->get('https://qyapi.weixin.qq.com/cgi-bin/gettoken', [
                'query' => [
                    'corpid'        => $this->corpid,
                    'corpsecret'    => $this->clientSecret,
                ],
            ]);

            return json_decode($response->getBody(), true);
        });

        return $access_token;
    }

    /**
     * Add config key 'corpid'.
     *
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return ['corpid'];
    }
}
