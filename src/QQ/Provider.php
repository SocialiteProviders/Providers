<?php

namespace SocialiteProviders\QQ;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'QQ';

    /**
     * @var string
     */
    private $openId;

    /**
     * User unionid.
     *
     * @var string
     */
    protected $unionId;

    /**
     * get token(openid) with unionid.
     *
     * @var bool
     */
    protected $withUnionId = false;

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['get_user_info'];

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getAuthUrl()
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://graph.qq.com/oauth2.0/authorize', $state);
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getTokenUrl()
     */
    protected function getTokenUrl()
    {
        return 'https://graph.qq.com/oauth2.0/token';
    }

    /**
     * @param  bool  $value
     * @return self
     */
    public function withUnionId($value = true)
    {
        $this->withUnionId = $value;

        return $this;
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getUserByToken()
     */
    protected function getUserByToken($token)
    {
        $url = 'https://graph.qq.com/oauth2.0/me?fmt=json&access_token='.$token;
        $this->withUnionId && $url .= '&unionid=1';

        $response = $this->getHttpClient()->get($url);

        $me = json_decode((string) $response->getBody(), true);
        $this->openId = $me['openid'];
        $this->unionId = $me['unionid'] ?? '';

        $response = $this->getHttpClient()->get(
            "https://graph.qq.com/user/get_user_info?access_token=$token&openid={$this->openId}&oauth_consumer_key={$this->clientId}"
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::mapUserToObject()
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'   => $this->openId, 'unionid' => $this->unionId, 'nickname' => $user['nickname'],
            'name' => null, 'email' => null, 'avatar' => $user['figureurl_qq_2'],
        ]);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'fmt' => 'json',
        ]);
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getAccessToken()
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            RequestOptions::QUERY => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
