<?php

namespace SocialiteProviders\Battlenet;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'BATTLENET';

    protected $scopeSeparator = '+';

    protected static $region;

    protected static array $additionalConfigKeys = ['region'];

    protected function getAuthUrl($state): string
    {
        $url = $this->isChina() ?
            'https://www.battlenet.com.cn/oauth/authorize' :
            'https://'.$this->getRegion().'.battle.net/oauth/authorize';

        return $this->buildAuthUrlFromBase($url, $state);
    }

    protected function getTokenUrl(): string
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
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['battletag'],
            'name'     => null,
            'email'    => null,
            'avatar'   => null,
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

    public static function setRegion($region)
    {
        self::$region = $region;
    }
}
