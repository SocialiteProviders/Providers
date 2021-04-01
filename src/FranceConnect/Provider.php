<?php

namespace SocialiteProviders\FranceConnect;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * API URLs.
     */
    public const PROD_BASE_URL = 'https://app.franceconnect.gouv.fr/api/v1';
    public const TEST_BASE_URL = 'https://fcp.integ01.dev-franceconnect.fr/api/v1';

    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'FRANCECONNECT';

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
     * Return API Base URL.
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        return config('app.env') === 'production' ? self::PROD_BASE_URL : self::TEST_BASE_URL;
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['logout_redirect'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        //It is used to prevent replay attacks
        $this->parameters['nonce'] = str_random(20);

        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getBaseUrl().'/token', [
            'headers'     => ['Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret)],
            'form_params' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody()->getContents(), true);
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

        //store tokenId session for logout url generation
        session()->put('fc_token_id', Arr::get($response, 'id_token'));

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
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
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
     *  Generate logout URL for redirection to FranceConnect.
     */
    public function generateLogoutURL()
    {
        $params = [
            'post_logout_redirect_uri' => config('services.franceconnect.logout_redirect'),
            'id_token_hint'            => session('fc_token_id'),
        ];

        return $this->getBaseUrl().'/logout?'.http_build_query($params);
    }
}
