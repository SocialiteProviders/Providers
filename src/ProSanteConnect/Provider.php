<?php

namespace SocialiteProviders\ProSanteConnect;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * API URLs.
     */
    public const PROD_AUTH_BASE_URL = 'https://wallet.esw.esante.gouv.fr/auth';
    public const TEST_AUTH_BASE_URL = 'https://wallet.bas.esw.esante.gouv.fr/auth';
    public const PROD_BASE_URL = 'https://auth.esw.esante.gouv.fr/auth/realms/esante-wallet/protocol/openid-connect';
    public const TEST_BASE_URL = 'https://auth.bas.esw.esante.gouv.fr/auth/realms/esante-wallet/protocol/openid-connect';

    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'PROSANTECONNECT';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'openid',
        'scope_all',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * Return API auth Base URL.
     *
     * @return string
     */
    protected function getAuthBaseUrl(): string
    {
        return config('app.env') === 'production' ? self::PROD_AUTH_BASE_URL : self::TEST_AUTH_BASE_URL;
    }

    /**
     * Return API Base URL.
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return config('app.env') === 'production' ? self::PROD_BASE_URL : self::TEST_BASE_URL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        //It is used to prevent replay attacks
        $this->parameters['nonce'] = str::random(20);
        //the acr values that the Authorization Server is being requested to use to process this authentication request
        $this->parameters['acr_values'] = 'eidas2';

        return $this->buildAuthUrlFromBase($this->getAuthBaseUrl().'/', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl().'/token';
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
    protected function getTokenFields($code): array
    {
        return Arr::add(
            parent::getTokenFields($code),
            'grant_type',
            'authorization_code',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
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
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/userinfo', [
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
            'name'                   => $user['given_name'],
            'family_name'            => $user['family_name'],
            'preferred_username'     => $user['preferred_username'],
        ]);
    }
}
