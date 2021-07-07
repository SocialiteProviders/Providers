<?php

namespace SocialiteProviders\Eveonline;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'EVEONLINE';

    /**
     * Tranquility endpoint for retrieving user info.
     */
    public const TRANQUILITY_ENDPOINT = 'https://login.eveonline.com';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://login.eveonline.com/v2/oauth/authorize/', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://login.eveonline.com/v2/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $authorization = 'Basic '.base64_encode($this->getConfig('client_id').':'.$this->getConfig('client_secret'));

        $response = $this->getHttpClient()->post('https://login.eveonline.com/v2/oauth/token', [
            'headers' => [
                'Authorization' => $authorization,
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code'       => $code,
            ],
        ]);

        // Vaules are access_token // expires_in // token_type // refresh_token
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        return static::verify($token);
    }

    public static function verify($jwt)
    {
        $endpoint = 'https://login.eveonline.com/oauth/jwks';

        // GETJWT information
        $response_jwks = (new Client())->get($endpoint);
        $response_jwks_info = json_decode($response_jwks->getBody()->getContents(), true);
        $decoded = JWT::decode($jwt, JWK::parseKeySet($response_jwks_info), ['RS256']);
        $decoded_array = (array) $decoded;
        if ($decoded_array['iss'] === 'login.eveonline.com' or $decoded_array['iss'] === self::TRANQUILITY_ENDPOINT) {
            if (strtotime('now') < $decoded_array['exp']) {
                return $decoded_array;
            } else {
                throw new InvalidStateException('Error with token expiration');
            }
        } else {
            throw new InvalidStateException('Access token issuer mismatch');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'character_owner_hash' => $user['owner'],
            'character_name'       => $user['name'],
            'character_id'         => ltrim($user['sub'], 'CHARACTER:EVE:'),
        ]);
    }
}
