<?php

namespace SocialiteProviders\HarID;

use Illuminate\Support\Facades\Cache;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'HARID';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'openid',
        'profile',
        'email',
        'session_type',
    ];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
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
     * Should use test or live IdP.
     *
     * @return bool
     */
    protected function useTestIdp()
    {
        return (bool) $this->getConfig('use_test_idp', false);
    }

    /**
     * Returns well known configuration for IdP.
     *
     * @return array
     */
    protected function getWellKnownConfiguration()
    {
        $url = $this->useTestIdp() ? 'https://test.harid.ee/.well-known/openid-configuration' : 'https://harid.ee/.well-known/openid-configuration';

        $value = Cache::remember($url, 60 * 60 * 24, function () use ($url) {
            $response = $this->getHttpClient()->get($url, [
                'query' => [
                    'prettyPrint' => 'false',
                ],
            ]);

            return (string) $response->getBody();
        });

        return json_decode($value, true);
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
            'query' => [
                'prettyPrint' => 'false',
            ],
            'headers' => [
                'Accept'        => 'application/json',
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
            'id'       => $user['sub'],
            'nickname' => $user['sub'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => '',
        ]);
    }
}
