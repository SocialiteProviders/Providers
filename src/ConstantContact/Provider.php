<?php

namespace SocialiteProviders\ConstantContact;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://v3.developer.constantcontact.com/api_guide/server_flow.html
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'CONSTANTCONTACT';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://authz.constantcontact.com/oauth2/default/v1/authorize',
            $state
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS     => [
                'Accept'        => 'application/json',
                'Authorization' => 'Basic '.base64_encode("{$this->clientId}:{$this->clientSecret}"),
            ],
            RequestOptions::FORM_PARAMS => array_diff_key(
                $this->getTokenFields($code),
                array_flip(['client_id', 'client_secret'])
            ),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://authz.constantcontact.com/oauth2/default/v1/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.cc.email/v3/account/summary',
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
        return (new User())->setRaw($user)->map([
            'id'       => $user['encoded_account_id'],
            'nickname' => null,
            'name'     => $user['first_name'].' '.$user['last_name'],
            'email'    => $user['contact_email'],
            'avatar'   => null,
        ]);
    }
}
