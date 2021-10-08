<?php

namespace SocialiteProviders\AzureADB2C;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
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
        'openid',
    ];

    /**
     * Get OpenID Configuration
     */
    private function getOpenIdConfiguration()
    {
        try {
            $response = $this->getHttpClient()->get(
                sprintf(
                    'https://%s.b2clogin.com/%s.onmicrosoft.com/%s/v2.0/.well-known/openid-configuration',
                    $this->getConfig('domain'),
                    $this->getConfig('domain'),
                    $this->getConfig('policy')
                ),
            );
        } catch (Exception $ex) {
            throw new InvalidStateException("Error on getting OpenID Configuration. {$ex}");
        }

        return json_decode($response->getBody());
    }

    /**
     * Get public keys to verify id_token from jwks_uri.
     */
    private function getJWTKeys()
    {
        $response = $this->getHttpClient()->get($this->getOpenIdConfiguration()->jwks_uri);

        return json_decode($response->getBody(), true);
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
     * Additional implementation to get user claims from id_token.
     */
    public function user()
    {
        $response = $this->getAccessTokenResponse($this->getCode());
        $claims = $this->validateIdToken(Arr::get($response, 'id_token'));

        return $this->mapUserToObject($claims);
    }

    /**
     * validate id_token
     * - signature validation using firebase/jwt library.
     * - claims validation
     *   iss: MUST much iss = issuer value on metadata.
     *   aud: MUST include client_id for this client.
     *   exp: MUST time() < exp.
     */
    private function validateIdToken($id_token)
    {
        try {
            // payload validation
            $payload = explode('.', $id_token);
            $payload_json = json_decode(base64_decode(str_pad(strtr($payload[1], '-_', '+/'), strlen($payload[1]) % 4, '=', STR_PAD_RIGHT)), true);

            // iss validation
            if (strcmp($payload_json['iss'], $this->getOpenIdConfiguration()->issuer)) {
                throw new InvalidStateException('iss on id_token does not match issuer value on the OpenID configuration');
            }
            // aud validation
            if (strpos($payload_json['aud'], $this->config['client_id']) === false) {
                throw new InvalidStateException('aud on id_token does not match the client_id for this application');
            }
            // exp validation
            if ((int) $payload_json['exp'] < time()) {
                throw new InvalidStateException('id_token is expired');
            }

            // signature validation and return claims
            return (array) JWT::decode($id_token, JWK::parseKeySet($this->getJWTKeys()), $this->getOpenIdConfiguration()->id_token_signing_alg_values_supported);
        } catch (Exception $ex) {
            throw new InvalidStateException("Error on validationg id_token. {$ex}");
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
     * return logout endpoint with post_logout_uri paramter.
     */
    public function logout($post_logout_uri)
    {
        return $this->getOpenIdConfiguration()->end_session_endpoint
            .'?logout&post_logout_redirect_uri='
            .urlencode($post_logout_uri);
    }

    /**
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return [
            'domain',
            'policy',
        ];
    }
}
