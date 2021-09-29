<?php

namespace SocialiteProviders\Cognito;

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
     * Scopes defintions.
     */
    public const SCOPE_OPENID = 'openid';
    public const SCOPE_EMAIL = 'email';
    public const SCOPE_PHONE = 'phone';
    public const SCOPE_PROFILE = 'profile';
    public const SCOPE_ADMIN = 'aws.cognito.signin.user.admin';

    /**
     * {@inheritdoc}
     * Adjust the available read / write attributes in cognito client app.
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
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return [
            'host',
            'authorize_uri',
            'token_uri',
            'userinfo_uri',
            'userinfo_key',
            'user_id',
            'user_nickname',
            'user_name',
            'user_email',
            'user_avatar',
        ];
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     *
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getCognitoUrl('authorize_uri'), $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return $this->getCognitoUrl('token_uri');
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     *
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getCognitoUrl('userinfo_uri'), [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return (array) json_decode($response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     *
     * @return \Laravel\Socialite\User
     */
    protected function mapUserToObject(array $user)
    {
        $key = $this->getConfig('userinfo_key', null);
        $data = is_null($key) === true ? $user : Arr::get($user, $key, []);

        return (new User())->setRaw($data)->map([
            'id'       => $this->getUserData($data, 'sub'),
            'nickname' => $this->getUserData($data, 'nickname'),
            'name'     => $this->getUserData($data, 'name'),
            'email'    => $this->getUserData($data, 'email'),
            'avatar'   => $this->getUserData($data, 'picture'),

            // user - array containing all available user attributes
            // Cognito adheres to OIDC standard claims - https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
        ]);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    protected function getCognitoUrl($type)
    {
        return rtrim($this->getConfig('host'), '/').'/'.ltrim(($this->getConfig($type, Arr::get([
            'authorize_uri' => 'oauth2/authorize',
            'token_uri'     => 'oauth2/token',
            'userinfo_uri'  => 'oauth2/userInfo',
        ], $type))), '/');
    }

    protected function getUserData($user, $key)
    {
        return Arr::get($user, $this->getConfig('user_'.$key, $key));
    }

    // Logout the user from cognito (expire SSO tokens), then redirect to url.
    public function logoutCognitoUser()
    {
        $authHost = config('services.cognito.host');
        $clientId = config('services.cognito.client_id');
        $logoutUri = config('services.cognito.logout_uri');

        return "$authHost/logout?client_id=$clientId&logout_uri=$logoutUri";
    }

    // Logout the user from cognito (expire SSO tokens), then redirect the user to he sign in page.
    public function switchCognitoUser()
    {
        $authHost = config('services.cognito.host');
        $clientId = config('services.cognito.client_id');
        $redirectUri = config('services.cognito.redirect');
        $scope = $this->formatScopes($this->getScopes(), $this->scopeSeparator);

        return "$authHost/logout?client_id=$clientId&response_type=code&scope=$scope&redirect_uri=$redirectUri";
    }
}
