<?php

namespace SocialiteProviders\LifeScienceLogin;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'LIFESCIENCELOGIN';

    /**
     * LifeScience Login config URL.
     */
    const CONFIG_URL = 'https://proxy.aai.lifescience-ri.eu/.well-known/openid-configuration';

    /**
     * Cache key for the OpenID config.
     */
    const CACHE_KEY = 'lslogin_openid_config';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $config = $this->getOpenidConfig();

        return $this->buildAuthUrlFromBase($config->authorization_endpoint, $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $config = $this->getOpenidConfig();

        return $config->token_endpoint;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $config = $this->getOpenidConfig();

        $response = $this->getHttpClient()->get($config->userinfo_endpoint, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
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
            'id'       => $user['id'],
            'name'     => $user['name'],
            'email'    => $user['email'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code'
        ]);
    }

    /**
     * Get OpenID Configuration.
     *
     * @throws Laravel\Socialite\Two\InvalidStateException
     *
     * @return mixed
     */
    private function getOpenIdConfiguration()
    {
        $expires = Carbon::now()->addHour();
        $config = Cache::remember(self::CACHE_KEY, $expires, function () {
            try {
                $response = $this->getHttpClient()->get(self::CONFIG_URL);
            } catch (Exception $e) {
                throw new InvalidStateException("Error on getting OpenID Configuration. {$e}");
            }

            return json_decode($response->getBody());
        });

        return $config;
    }
}
