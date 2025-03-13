<?php

namespace SocialiteProviders\NfdiLogin;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://nfdi-aai.de/infraproxy
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'NFDILOGIN';

    /**
     * NFDI Login config URL.
     */
    public const CONFIG_URL = 'https://infraproxy.nfdi-aai.dfn.de/.well-known/openid-configuration';

    /**
     * Cache key for the OpenID config.
     */
    public const CACHE_KEY = 'nfdi_login_openid_config';

    protected $scopes = ['openid', 'email'];

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        // Remove client ID and secret because they are sent in the Authorization header.
        $fields = parent::getTokenFields($code);
        unset($fields['client_id']);
        unset($fields['client_secret']);

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenHeaders($code)
    {
        $headers = parent::getTokenHeaders($code);
        $headers['Authorization'] = 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret);

        return $headers;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        $config = $this->getOpenIdConfiguration();

        return $this->buildAuthUrlFromBase($config->authorization_endpoint, $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
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
            'name'        => $user['name'] ?? '',
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

        return Cache::remember(self::CACHE_KEY, $expires, function () {
            try {
                $response = $this->getHttpClient()->get(self::CONFIG_URL);
            } catch (Exception $e) {
                throw new InvalidStateException("Error on getting OpenID Configuration. {$e}");
            }

            return json_decode((string) $response->getBody());
        });
    }
}
