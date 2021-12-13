<?php

namespace SocialiteProviders\Bitrix24;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'BITRIX24';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];

    /**
     * {@inheritdoc}
     */
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
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function getPortalUrl()
    {
        $endpoint = $this->getConfig('endpoint');

        if ($endpoint === null) {
            throw new InvalidArgumentException('Bitrix24 endpoint URI must be set.');
        }

        return $endpoint;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.bitrix.info/oauth/token/';
    }

    /**
     * Get the user by token.
     *
     * @param string $token
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getPortalUrl().'/rest/user.current/', [
            RequestOptions::QUERY => [
                'auth' => $token,
            ],
        ]);

        $user = json_decode($response->getBody(), true);

        if (isset($user['error'])) {
            throw new RuntimeException($user['error'].': '.$user['error_description'], 403);
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
}
