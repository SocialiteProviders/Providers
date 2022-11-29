<?php

namespace SocialiteProviders\Onelogin;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'ONELOGIN';

    /**
     * Scopes defintions.
     *
     * @see https://developers.onelogin.com/openid-connect/scopes
     */
    public const SCOPE_OPENID = 'openid';
    public const SCOPE_PROFILE = 'profile';
    public const SCOPE_EMAIL = 'email';
    public const SCOPE_NAME = 'name';
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

    protected $scopeSeparator = ' ';

    protected function getOneloginUrl()
    {
        return $this->getConfig('base_url');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getOneloginUrl().'/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getOneloginUrl().'/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getOneloginUrl().'/me', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the client access token response.
     *
     * @param array|string $scopes
     *
     * @return array
     */
    public function getClientAccessTokenResponse($scopes = null)
    {
        $scopes = $scopes ?? $this->getScopes();
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
     * @param string $refreshToken
     *
     * @return array
     */
    public function getRefreshTokenResponse(string $refreshToken)
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
            'id'                    => Arr::get($user, 'sub'),
            'email'                 => Arr::get($user, 'email'),
            'email_verified'        => Arr::get($user, 'email_verified', false),
            'preferred_username'    => Arr::get($user, 'preferred_username'),
            'nickname'              => Arr::get($user, 'nickname'),
            'name'                  => Arr::get($user, 'name'),
            'first_name'            => Arr::get($user, 'given_name'),
            'last_name'             => Arr::get($user, 'family_name'),
            'groups'                => Arr::get($user, 'groups'),
            'locale'                => Arr::get($user, 'locale'),
            'phone'                 => Arr::get($user, 'phone_number'),
            'id_token'              => $this->credentialsResponseBody['id_token'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * @param string      $idToken
     * @param string|null $redirectUri
     * @param string|null $state
     *
     * @return string
     */
    public function getLogoutUrl(string $idToken, string $redirectUri = null, bool $logout = true, string $state = null)
    {
        $url = $this->getOneloginUrl().'/logout';

        $params = http_build_query(array_filter([
            'id_token_hint'            => $idToken,
            'post_logout_redirect_uri' => $redirectUri,
            'logout'                   => $logout,
            'state'                    => $state,
        ]));

        return "$url?$params";
    }

    /**
     * @param string $token
     * @param string $hint
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function revokeToken(string $token, string $hint = 'access_token')
    {
        $url = $this->getOneloginUrl().'/token/revocation';

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
     * @param string $token
     * @param string $hint
     *
     * @return array
     */
    public function introspectToken(string $token, string $hint = 'access_token')
    {
        $url = $this->getOneloginUrl().'/token/introspection';
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
