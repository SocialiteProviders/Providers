<?php

namespace SocialiteProviders\WxWork;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\Contracts\ConfigInterface;
use SocialiteProviders\Manager\OAuth2\User;
use Illuminate\Support\Facades\Cache;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'WXWORK';


    /**
     * {@inheritdoc}.
     */
    protected $scopes = ['snsapi_base'];

    protected $userid;

    /**
     * @param \SocialiteProviders\Manager\Contracts\OAuth1\ProviderInterface|\SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface $config
     */
    public function setConfig(ConfigInterface $config)
    {

        $config = config("services.wxwork");

        $this->config       = $config;
        $this->corpid       = $config['corpid'];
        $this->clientId     = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->redirectUrl  = $config['redirect'];

        return $this;
    }

    /**
     * {@inheritdoc}.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://open.work.weixin.qq.com/wwopen/sso/qrConnect', $state);
    }

    /**
     * {@inheritdoc}.
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        $query = http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);

        return $url . '?' . $query;
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
            'state'         => $state
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
                'userid'       => $this->userid
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
            'gender'    => $user['gender']
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
                'code'          => $code
            ],
        ]);
        $this->userid = json_decode($response->getBody(), true)['UserId'];
        $this->credentialsResponseBody = $this->getAccessToken();
        return $this->credentialsResponseBody;
    }

    /**
     * get access token
     *
     * @return void
     */
    protected function getAccessToken()
    {
        $cache_key = 'WXWORK_' . $this->clientId;
        $access_token = Cache::remember($cache_key, 7200, function () {
            $response = $this->getHttpClient()->get('https://qyapi.weixin.qq.com/cgi-bin/gettoken', [
                'query' => [
                    'corpid'        => $this->corpid,
                    'corpsecret'    => $this->clientSecret
                ],
            ]);

            return json_decode($response->getBody(), true);
        });

        return $access_token;
    }
}
