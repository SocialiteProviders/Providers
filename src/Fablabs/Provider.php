<?php

namespace SocialiteProviders\Fablabs;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FABLABS';

    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getInstanceUri().'oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getInstanceUri().'oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getInstanceUri().'0/me.json', [
            RequestOptions::HEADERS => [
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
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => trim($user['first_name'].' '.$user['last_name']),
            'email'    => $user['email'],
            'avatar'   => $user['avatar_url'],
        ]);
    }

    protected function getInstanceUri()
    {
        return $this->getConfig('instance_uri', 'https://api.fablabs.io/');
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['instance_uri'];
    }
}
