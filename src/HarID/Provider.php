<?php

namespace SocialiteProviders\HarID;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
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
     * Returns base URL for idP endpoints with check for test or live environment.
     *
     * @return string
     */
    protected function getEndpointBaseUrl()
    {
        return (bool) $this->getConfig('use_test_idp', false) === true ? 'https://test.harid.ee/et' : 'https://harid.ee/et';
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getEndpointBaseUrl().'/authorizations/new', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getEndpointBaseUrl().'/access_tokens';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getEndpointBaseUrl().'/user_info', [
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
        return (new User)->setRaw($user)->map([
            'id'       => $user['sub'],
            'nickname' => $user['sub'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => '',
        ]);
    }
}
