<?php

namespace SocialiteProviders\Bitrix24;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Bitrix24Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'BITRIX24';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];

    public static function additionalConfigKeys()
    {
        return ['endpoint'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getPortalUrl().'/oauth/authorize', $state);
    }

    /**
     * Get the portal URL.
     *
     * @return string
     */
    protected function getPortalUrl()
    {
        return $this->getConfig('endpoint');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.bitrix.info/oauth/token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getPortalUrl().'/rest/user.current/', [
            'query' => [
                'auth' => $token,
            ],
        ]);

        $user = json_decode($response->getBody(), true);
        if (isset($user['error'])) {
            throw new \Exception($user['error'].': '.$user['error_description'], 403);
        }

        return $user['result'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'    => $user['ID'],
            'name'  => trim($user['NAME'].' '.$user['LAST_NAME']),
            'email' => $user['EMAIL'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
