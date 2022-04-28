<?php

namespace SocialiteProviders\AmoCRM;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'AMOCRM';

    /**
     * @return string
     */
    protected function getBaseAmoUrl()
    {
        return "https://www.amocrm.{$this->getConfig('domain')}";
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['domain'];
    }

    /**
     * {@inheritDoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase("{$this->getBaseAmoUrl()}/oauth", $state);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenUrl()
    {
        $domain = request('referer');

        return "https://{$domain}/oauth2/access_token";
    }

    /**
     * {@inheritDoc}
     */
    protected function getUserByToken($token)
    {
        $domain = request('referer');

        $response = $this->getHttpClient()->get("https://{$domain}/api/v4/account", [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        $account = json_decode((string) $response->getBody(), true);
        $response = $this->getHttpClient()->get("https://{$domain}/api/v4/users/{$account['current_user_id']}", [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $user['name'],
            'nickname' => $user['email'],
            'email' => $user['email'],
        ]);
    }
}
