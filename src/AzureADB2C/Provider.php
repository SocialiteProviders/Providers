<?php

namespace SocialiteProviders\AzureADB2C;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'AZUREADB2C';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
    ];

    /**
     * Get the policy.
     *
     * @return string
     */
    private function getPolicy()
    {
        return $this->parameters['policy'] ?? 'login';
    }

    /**
     * Get the B2C policy.
     *
     * @return mixed
     */
    private function getB2CPolicy()
    {
        $policy = $this->getConfig('policy');
        if (is_array($policy)) {
            $policyKey = $this->getPolicy();
            $policy = $policy[$policyKey] ?? reset($policy);
        }

        return $policy;
    }

    /**
     * Set the redirect URL.
     *
     * @return void
     */
    private function setRedirectUrl()
    {
        $redirectTemplate = $this->getConfig('redirect_template');
        if (empty($redirectTemplate)) {
            return;
        }

        $redirect = str_replace('{policy}', $this->getPolicy(), $redirectTemplate);

        $url = Str::startsWith($redirect, '/')
            ? URL::to($redirect)
            : $redirect;

        $this->redirectUrl($url);
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
        $this->setRedirectUrl();

        try {
            $discovery = sprintf(
                'https://%s.b2clogin.com/%s.onmicrosoft.com/%s/v2.0/.well-known/openid-configuration',
                $this->getConfig('domain'),
                $this->getConfig('domain'),
                $this->getB2CPolicy()
            );

            $response = $this->getHttpClient()->get($discovery);
        } catch (Exception $ex) {
            throw new InvalidStateException("Error on getting OpenID Configuration. {$ex}");
        }

        return json_decode((string) $response->getBody());
    }

    /**
     * Get public keys to verify id_token from jwks_uri.
     *
     * @return array
     */
    private function getJWTKeys()
    {
        $response = $this->getHttpClient()->get($this->getOpenIdConfiguration()->jwks_uri);

        return json_decode((string) $response->getBody(), true);
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
     *
     * @return \SocialiteProviders\Manager\OAuth2\User
     */
    public function user()
    {
        $this->setRedirectUrl();

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
     *
     * @param string $idToken
     *
     * @throws Laravel\Socialite\Two\InvalidStateException
     *
     * @return array
     */
    private function validateIdToken($idToken)
    {
        try {
            // payload validation
            $payload = explode('.', $idToken);
            $payloadJson = json_decode(base64_decode(str_pad(strtr($payload[1], '-_', '+/'), strlen($payload[1]) % 4, '=', STR_PAD_RIGHT)), true);

            // iss validation
            if (strcmp($payloadJson['iss'], $this->getOpenIdConfiguration()->issuer)) {
                throw new InvalidStateException('iss on id_token does not match issuer value on the OpenID configuration');
            }
            // aud validation
            if (strpos($payloadJson['aud'], $this->config['client_id']) === false) {
                throw new InvalidStateException('aud on id_token does not match the client_id for this application');
            }
            // exp validation
            if ((int) $payloadJson['exp'] < time()) {
                throw new InvalidStateException('id_token is expired');
            }

            // signature validation and return claims
            return (array) JWT::decode($idToken, JWK::parseKeySet($this->getJWTKeys(), $this->getConfig('default_algorithm')), $this->getOpenIdConfiguration()->id_token_signing_alg_values_supported);
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
        ]);
    }

    /**
     * return logout endpoint with post_logout_uri paramter.
     *
     * @return string
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
            'redirect_template',
            'default_algorithm',
        ];
    }
}
