<?php

namespace SocialiteProviders\HarID;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'HARID';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
        'profile',
        'email',
        'session_type',
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
        return ['use_test_idp'];
    }

    /**
     * Returns well known configuration for IdP.
     *
     * @return array
     */
    protected function getWellKnownConfiguration()
    {
        $url = ($this->getConfig('use_test_idp', false) === true)  ? 'https://test.harid.ee/.well-known/openid-configuration' : 'https://harid.ee/.well-known/openid-configuration';

        $response = $this->getHttpClient()->get($url, [
            RequestOptions::QUERY => [
                'prettyPrint' => 'false',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getWellKnownConfiguration()['authorization_endpoint'], $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getWellKnownConfiguration()['token_endpoint'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getWellKnownConfiguration()['userinfo_endpoint'], [
            RequestOptions::QUERY => [
                'prettyPrint' => 'false',
            ],
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
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
            'id'       => $user['sub'],
            'nickname' => $user['sub'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => '',
        ]);
    }
}
