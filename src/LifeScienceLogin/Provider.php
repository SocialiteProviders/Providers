<?php

namespace SocialiteProviders\LifeScienceLogin;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://lifescience-ri.eu/ls-login/documentation/service-provider-documentation/service-provider-documentation.html
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'LIFESCIENCELOGIN';

    /**
     * LifeScience Login config URL.
     */
    public const CONFIG_URL = 'https://login.aai.lifescience-ri.eu/oidc/.well-known/openid-configuration';

    /**
     * Cache key for the OpenID config.
     */
    public const CACHE_KEY = 'lslogin_openid_config';

    /**
     * {@inheritdoc}
     */
    protected $usesPKCE = true;

    protected $scopeSeparator = ' ';

    protected $scopes = ['openid', 'email', 'profile'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $config = $this->getOpenIdConfiguration();

        return $this->buildAuthUrlFromBase($config->authorization_endpoint, $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $config = $this->getOpenIdConfiguration();

        return $config->token_endpoint;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $config = $this->getOpenIdConfiguration();

        $response = $this->getHttpClient()->get($config->userinfo_endpoint, [
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
            'id'          => $user['sub'],
            'name'        => $user['name'],
            'given_name'  => $user['given_name'],
            'family_name' => $user['family_name'],
            'email'       => $user['email'],
        ]);
    }

    /**
     * Get OpenID Configuration.
     *
     * @return mixed
     *
     * @throws Laravel\Socialite\Two\InvalidStateException
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

            return json_decode((string) $response->getBody());
        });

        return $config;
    }
}
