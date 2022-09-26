<?php

namespace SocialiteProviders\Pinterest;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PINTEREST';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user_accounts:read'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://www.pinterest.com/oauth/',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.pinterest.com/v5/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.pinterest.com/v5/user_account',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map(
            [
                'id'       => $user['username'],
                'nickname' => $user['username'],
                'name'     => null,
                'email'    => null,
                'avatar'   => $user['profile_image'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $fields = [
            'grant_type' => 'authorization_code',
            //'client_id' => $this->clientId,
            //'client_secret' => $this->clientSecret,
            'code'         => $code,
            'redirect_uri' => $this->redirectUrl,
        ];

        if ($this->usesPKCE()) {
            $fields['code_verifier'] = $this->request->session()->pull('code_verifier');
        }

        return $fields;
    }

    /**
     * Get the access token response for the given code.
     *
     * @param string $code
     *
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Content-type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
            ],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }
}
