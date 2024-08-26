<?php

namespace SocialiteProviders\Cognito;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'COGNITO';

    /**
     * Scope definitions.
     */
    public const SCOPE_ADMIN = 'aws.cognito.signin.user.admin';

    public const SCOPE_EMAIL = 'email';

    public const SCOPE_OPENID = 'openid';

    public const SCOPE_PHONE = 'phone';

    public const SCOPE_PROFILE = 'profile';

    /**
     * Adjust the available read / write attributes in cognito client app.
     *
     * {@inheritdoc}
     */
    protected $scopes = [
        self::SCOPE_OPENID,
        self::SCOPE_PROFILE,
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * Get the host URL.
     *
     * @return string
     */
    protected function getCognitoUrl()
    {
        return $this->getConfig('host');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getCognitoUrl().'/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return $this->getCognitoUrl().'/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->getCognitoUrl().'/oauth2/userInfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Returned user array containing all available user attributes
     * Cognito adheres to OIDC standard claims - https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims.
     *
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['sub'] ?? null,
            'nickname' => $user['nickname'] ?? null,
            'name'     => trim(Arr::get($user, 'given_name', '').' '.Arr::get($user, 'family_name', '')),
            'email'    => $user['email'] ?? null,
            'avatar'   => null,
        ]);
    }

    /**
     * Logout the user from cognito (expire SSO tokens), then redirect to url.
     *
     * {@inheritdoc}
     */
    public function logoutCognitoUser(): string
    {
        $authHost = $this->getConfig('host');
        $clientId = $this->getConfig('client_id');
        $logoutUri = $this->getConfig('logout_uri');

        return sprintf('%s/logout?client_id=%s&logout_uri=%s', $authHost, $clientId, $logoutUri);
    }

    /**
     * Logout the user from cognito (expire SSO tokens), then redirect the user to the sign-in page.
     *
     * {@inheritdoc}
     */
    public function switchCognitoUser(): string
    {
        $authHost = $this->getConfig('host');

        return sprintf('%s/logout?', $authHost).http_build_query([
            'client_id'     => $this->getConfig('client_id'),
            'redirect_uri'  => $this->getConfig('redirect'),
            'response_type' => 'code',
            'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return [
            'client_id',
            'host',
            'logout_uri',
            'redirect',
        ];
    }
}
