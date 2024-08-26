<?php

namespace SocialiteProviders\Xero;

use Firebase\JWT\JWT;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'XERO';

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
        'profile',
        'email',
        'accounting.transactions',
        'accounting.settings',
        'accounting.contacts',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://login.xero.com/identity/connect/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://identity.xero.com/connect/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.xero.com/connections', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $connections)
    {
        $idToken = $this->credentialsResponseBody['id_token'];
        $bodyb64 = explode('.', $idToken)[1];
        $jwtDecoded = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));

        return (new User)->map([
            'id'       => $jwtDecoded->xero_userid,
            'nickname' => $jwtDecoded->given_name,
            'name'     => $jwtDecoded->given_name.' '.$jwtDecoded->family_name,
            'email'    => $jwtDecoded->email,
            'tenants'  => $connections,
        ]);
    }
}
