<?php

namespace SocialiteProviders\Nextcloud;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/oauth2.html
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'NEXTCLOUD';

    protected $scopeSeparator = ' ';

    protected static array $additionalConfigKeys = ['instance_uri'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getInstanceUri().'/apps/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getInstanceUri().'/apps/oauth2/api/v1/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getInstanceUri().'/ocs/v2.php/cloud/user', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'format' => 'json',
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
            'id'       => $user['ocs']['data']['id'],
            'nickname' => $user['ocs']['data']['id'],
            'name'     => $user['ocs']['data']['display-name'],
            'email'    => $user['ocs']['data']['email'],
        ]);
    }

    protected function getInstanceUri()
    {
        return $this->getConfig('instance_uri');
    }
}
