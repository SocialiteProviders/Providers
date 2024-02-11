<?php

namespace SocialiteProviders\Okta;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'OKTA';

    /**
     * Scopes defintions.
     *
     * @see https://developer.okta.com/docs/reference/api/oidc/#scopes
     */
    public const SCOPE_OPENID = 'openid';

    public const SCOPE_PROFILE = 'profile';

    public const SCOPE_EMAIL = 'email';

    public const SCOPE_ADDRESS = 'address';

    public const SCOPE_PHONE = 'phone';

    public const SCOPE_OFFLINE_ACCESS = 'offline_access';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        self::SCOPE_OPENID,
        self::SCOPE_PROFILE,
        self::SCOPE_EMAIL,
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    protected function getOktaUrl()
    {
        return $this->getConfig('base_url');
    }

    /**
     * Returns the Auth Server ID based on config option 'auth_server_id'.
     *
     * @return string
     */
    protected function getAuthServerId()
    {
        $authServerId = (string) $this->getConfig('auth_server_id');

        return $authServerId === '' ? $authServerId : $authServerId.'/';
    }

    /**
     * Get the Okta sever URL.
     *
     * @return string
     */
    protected function getOktaServerUrl(): string
    {
        return $this->getOktaUrl().'/oauth2/'.$this->getAuthServerId();
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['base_url', 'auth_server_id'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getOktaServerUrl().'v1/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getOktaServerUrl().'v1/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getOktaServerUrl().'v1/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Get the client access token response.
     *
     * @param  array|string  $scopes
     * @return array
     */
    public function getClientAccessTokenResponse($scopes = null)
    {
        $scopes ??= $this->getScopes();
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::AUTH        => [$this->clientId, $this->clientSecret],
            RequestOptions::HEADERS     => ['Cache-Control' => 'no-cache'],
            RequestOptions::FORM_PARAMS => [
                'grant_type' => 'client_credentials',
                'scope'      => $this->formatScopes((array) $scopes, $this->scopeSeparator),
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @param  string  $refreshToken
     * @return array|null
     */
    public function getRefreshTokenResponse($refreshToken)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::AUTH        => [$this->clientId, $this->clientSecret],
            RequestOptions::HEADERS     => ['Cache-Control' => 'no-cache'],
            RequestOptions::FORM_PARAMS => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
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
            'id'             => Arr::get($user, 'sub'),
            'email'          => Arr::get($user, 'email'),
            'email_verified' => Arr::get($user, 'email_verified', false),
            'nickname'       => Arr::get($user, 'nickname'),
            'name'           => Arr::get($user, 'name'),
            'first_name'     => Arr::get($user, 'given_name'),
            'last_name'      => Arr::get($user, 'family_name'),
            'profileUrl'     => Arr::get($user, 'profile'),
            'address'        => Arr::get($user, 'address'),
            'phone'          => Arr::get($user, 'phone'),
            'id_token'       => $this->credentialsResponseBody['id_token'] ?? null,
        ]);
    }

    /**
     * @param  string  $idToken
     * @param  string|null  $redirectUri
     * @param  string|null  $state
     * @return string
     */
    public function getLogoutUrl(string $idToken, string $redirectUri = null, string $state = null)
    {
        $url = $this->getOktaServerUrl().'v1/logout';

        $params = http_build_query(array_filter([
            'id_token_hint'            => $idToken,
            'post_logout_redirect_uri' => $redirectUri,
            'state'                    => $state,
        ]));

        return "$url?$params";
    }

    /**
     * @param  string  $token
     * @param  string  $hint
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function revokeToken(string $token, string $hint = 'access_token')
    {
        $url = $this->getOktaServerUrl().'v1/revoke';

        return $this->getHttpClient()->post($url, [
            RequestOptions::AUTH        => [$this->clientId, $this->clientSecret],
            RequestOptions::HEADERS     => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => [
                'token'           => $token,
                'token_type_hint' => $hint,
            ],
        ]);
    }

    /**
     * @param  string  $token
     * @param  string  $hint
     * @return array
     */
    public function introspectToken(string $token, string $hint = 'access_token')
    {
        $url = $this->getOktaServerUrl().'v1/introspect';
        $resp = $this->getHttpClient()->post($url, [
            RequestOptions::AUTH        => [$this->clientId, $this->clientSecret],
            RequestOptions::HEADERS     => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => [
                'token'           => $token,
                'token_type_hint' => $hint,
            ],
        ]);

        return json_decode((string) $resp->getBody(), true);
    }
}
