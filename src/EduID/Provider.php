<?php

namespace SocialiteProviders\EduID;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'EDUID';

    /**
     * Scopes defintions.
     *
     * @see https://help.switch.ch/eduid/docs/services/openid-connect/scopes/
     */
    public const SCOPE_OPENID = 'openid';

    public const SCOPE_PROFILE = 'profile';

    public const SCOPE_EMAIL = 'email';

    public const SCOPE_AUTHZ_USER_READ = 'https://login.eduid.ch/authz/User.Read';

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

    /**
     * Get the Edu ID server URL.
     *
     * @return string
     */
    protected function getEduIdUrl(): string
    {
        if ($this->getConfig('use_test_idp')) {
            return 'https://login.test.eduid.ch/idp/profile/oidc/';
        }

        return 'https://login.eduid.ch/idp/profile/oidc/';
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['use_test_idp'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getEduIdUrl().'authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getEduIdUrl().'token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getEduIdUrl().'userinfo', [
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
            'name'           => Arr::get($user, 'name'),
            'first_name'     => Arr::get($user, 'given_name'),
            'last_name'      => Arr::get($user, 'family_name'),
            'id_token'       => $this->credentialsResponseBody['id_token'] ?? null,
        ]);
    }

    /**
     * @param  string  $token
     * @param  string  $hint
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function revokeToken(string $token, string $hint = 'access_token')
    {
        $url = $this->getEduIdUrl().'revocation';

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
        $url = $this->getEduIdUrl().'introspection';
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
