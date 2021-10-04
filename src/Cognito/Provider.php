<?php

namespace SocialiteProviders\Cognito;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'COGNITO';

    /**
     * Scope definitions.
     */
    public const SCOPE_OPENID = 'openid';
    public const SCOPE_EMAIL = 'email';
    public const SCOPE_PHONE = 'phone';
    public const SCOPE_PROFILE = 'profile';
    public const SCOPE_ADMIN = 'aws.cognito.signin.user.admin';

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
        return (new User())->setRaw($user)->map([
            'id'       => $user['sub'],
            'nickname' => $user['nickname'],
            'name'     => Arr::get($user, 'given_name', '').' '.Arr::get($user, 'family_name', ''),
            'email'    => $user['email'],
            'avatar'   => null, // $user['picture']
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
     * Logout the user from cognito (expire SSO tokens), then redirect the user to he sign in page.
     *
     * {@inheritdoc}
     */
    public function switchCognitoUser(): string
    {
        $authHost = $this->getConfig('host');
        $clientId = $this->getConfig('client_id');
        $redirectUri = $this->getConfig('redirect');
        $scope = $this->formatScopes($this->getScopes(), $this->scopeSeparator);
        return sprintf('%s/logout?client_id=%s&response_type=code&scope=%s&redirect_uri=%s', $authHost, $clientId, $scope, $redirectUri);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return [
            'host',
            'client_id',
            'logout_uri',
            'redirect',
        ];
    }
}
