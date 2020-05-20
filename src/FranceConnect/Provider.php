<?php

namespace SocialiteProviders\FranceConnect;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * TEST API URL.
     */
    const TEST_AUTHORIZE_URL = 'https://fcp.integ01.dev-franceconnect.fr/api/v1/authorize';
    const TEST_TOKEN_URL = 'https://fcp.integ01.dev-franceconnect.fr/api/v1/token';
    const TEST_USERINFO_URL = 'https://fcp.integ01.dev-franceconnect.fr/api/v1/userinfo';
    const TEST_LOGOUT_URL = 'https://fcp.integ01.dev-franceconnect.fr/api/v1/logout';

    /**
     * PROD API URL.
     */
    const PROD_AUTHORIZE_URL = 'https://app.franceconnect.gouv.fr/api/v1/authorize';
    const PROD_TOKEN_URL = 'https://app.franceconnect.gouv.fr/api/v1/token';
    const PROD_USERINFO_URL = 'https://app.franceconnect.gouv.fr/api/v1/userinfo';
    const PROD_LOGOUT_URL = 'https://app.franceconnect.gouv.fr/api/v1/logout';

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'FRANCECONNECT';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'openid',
        'given_name',
        'family_name',
        'gender',
        'birthplace',
        'birthcountry',
        'email',
        'preferred_username',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        //It is used to prevent replay attacks
        $this->parameters['nonce'] = str_random(20);

        return $this->buildAuthUrlFromBase($this->getAuthorizeUrl(), $state);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers'     => ['Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret)],
            'form_params' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_add(
            parent::getTokenFields($code),
            'grant_type',
            'authorization_code'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->mapUserToObject($this->getUserByToken(
            $token = Arr::get($response, 'access_token')
        ));

        return  $user->setTokenId(Arr::get($response, 'id_token'))
                    ->setToken($token)
                    ->setRefreshToken(Arr::get($response, 'refresh_token'))
                    ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getUserInfoUrl(), [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'                     => $user['sub'],
            'given_name'             => $user['given_name'],
            'family_name'            => $user['family_name'],
            'gender'                 => $user['gender'],
            'birthplace'             => $user['birthplace'],
            'birthcountry'           => $user['birthcountry'],
            'email'                  => $user['email'],
            'preferred_username'     => $user['preferred_username'],
        ]);
    }

    /**
     * Generate  URL for Logout.
     *
     * @param string $tokenId
     *
     * @return string
     */
    public function generateLogoutURL($tokenId)
    {
        $params = [
            'post_logout_redirect_uri' => config('services.france_connect.logout_redirect'),
            'id_token_hint'            => $tokenId,
        ];

        return $this->getLogoutUrl().'?'.http_build_query($params);
    }

    /**
     * Return API Authorize URL.
     *
     * @return string
     */
    protected function getAuthorizeUrl()
    {
        return config('app.env') == 'production' ? self::PROD_AUTHORIZE_URL : self::TEST_AUTHORIZE_URL;
    }

    /**
     * Return API User Info URL.
     *
     * @return string
     */
    protected function getUserInfoUrl()
    {
        return config('app.env') == 'production' ? self::PROD_USERINFO_URL : self::TEST_USERINFO_URL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return config('app.env') == 'production' ? self::PROD_TOKEN_URL : self::TEST_TOKEN_URL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getLogoutUrl()
    {
        return config('app.env') == 'production' ? self::PROD_LOGOUT_URL : self::TEST_LOGOUT_URL;
    }
}
