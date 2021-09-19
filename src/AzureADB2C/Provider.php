<?php

namespace SocialiteProviders\AzureADB2C;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'AZUREADB2C';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid'
    ];

    /**
     * Get OpenID Configuration and store on cache
     */
    private function getOpenIdConfiguration() {
        return Cache::remember('socialite_' . self::IDENTIFIER . '_openidconfiguration', intval($this->config['cache_time'] ?: 3600), function () {
            try {
                $response = $this->getHttpClient()->get(
                    sprintf(
                        'https://%s.b2clogin.com/%s.onmicrosoft.com/%s/v2.0/.well-known/openid-configuration',
                        $this->getConfig('domain'),
                        $this->getConfig('domain'),
                        $this->getConfig('policy')
                        ),
                    ['http_errors' => true]);
            } catch(ClientException $ex) {
                throw new Exception("Error on getting OpenID Configuration. {$ex}");
            }
            return json_decode($response->getBody());
        });
    }

    /**
     * Get public keys to verify id_token from jwks_uri
     */
    private function getJWTKeys() {
        return Cache::remember('socialite_' . self::IDENTIFIER . '_jwtpublickeys', intval($this->config['cache_time'] ?: 3600), function () {
            $response = $this->getHttpClient()->get($this->getOpenIdConfiguration()->jwks_uri);
            return json_decode($response->getBody(), true);
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->getOpenIdConfiguration()->authorization_endpoint,
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getOpenIdConfiguration()->token_endpoint;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        // no implementation required because Azure AD B2C doesn't return access_token
    }

    /**
     * Additional implementation to get user claims from id_token
     */
    public function user()
    {
        try {
            $response = $this->getAccessTokenResponse($this->getCode());
            $claims = (array) JWT::decode(Arr::get($response, 'id_token'), JWK::parseKeySet($this->getJWTKeys()), $this->getOpenIdConfiguration()->id_token_signing_alg_values_supported);
            return $this->mapUserToObject($claims);

        } catch(Exeption $ex) {
            throw new Exception("Error on getting OpenID Configuration. {$ex}");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'   => $user['sub'],
            'name' => $user['name'],
        ]);
    }

    /**
     * return logout endpoint with post_logout_uri paramter
     */
    public function logout($post_logout_uri)
    {
        return $this->getOpenIdConfiguration()->end_session_endpoint
            . '?logout&post_logout_redirect_uri='
            . urlencode($post_logout_uri);
    }

    /**
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return [
            'domain',
            'policy',
            'cache_time'
        ];
    }
}
