<?php

namespace SocialiteProviders\Eveonline;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use UnexpectedValueException;

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
        $authorization = 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret);

        $response = $this->getHttpClient()->post('https://login.eveonline.com/v2/oauth/token', [
            'headers' => [
                'Authorization' => $authorization,
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code'       => $code,
            ],
        ]);

        // Values are access_token // expires_in // token_type // refresh_token
        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        return $this->verify($token);
    }

    /**
     * @param string $jwt
     *
     * @throws \UnexpectedValueException|\Firebase\JWT\ExpiredException
     *
     * @return array
     */
    public function verify($jwt)
    {
        $responseJwks = $this->getHttpClient()->get('https://login.eveonline.com/oauth/jwks');
        $responseJwksInfo = json_decode((string) $responseJwks->getBody(), true);
        $decodedArray = (array) JWT::decode($jwt, JWK::parseKeySet($responseJwksInfo), ['RS256']);

        if ($decodedArray['iss'] !== 'login.eveonline.com' && $decodedArray['iss'] !== self::TRANQUILITY_ENDPOINT) {
            throw new UnexpectedValueException('Access token issuer mismatch');
        }

        if (time() >= $decodedArray['exp']) {
            throw new ExpiredException();
        }

        return $decodedArray;
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
