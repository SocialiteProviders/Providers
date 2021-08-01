<?php

namespace SocialiteProviders\Battlenet;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'BATTLENET';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = '+';

    protected static $region;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $url = $this->isChina() ? 'https://www.battlenet.com.cn/oauth/authorize' : 'https://'.$this->getRegion().'.battle.net/oauth/authorize';

        return $this->buildAuthUrlFromBase($url, $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        if ($this->isChina()) {
            return 'https://www.battlenet.com.cn/oauth/token';
        }

        return "https://{$this->getRegion()}.battle.net/oauth/token";
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $url = $this->isChina() ? 'https://www.battlenet.com.cn/oauth/userinfo' : 'https://'.$this->getRegion().'.battle.net/oauth/userinfo';

        $response = $this->getHttpClient()->get($url, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['battletag'],
            'name'     => null,
            'email'    => null,
            'avatar'   => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    protected function getRegion()
    {
        if (self::$region) {
            return self::$region;
        }

        return $this->getConfig('region', 'us');
    }

    protected function isChina()
    {
        return strtolower($this->getRegion()) === 'cn';
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['region'];
    }

    public static function setRegion($region)
    {
        self::$region = $region;
    }
}
