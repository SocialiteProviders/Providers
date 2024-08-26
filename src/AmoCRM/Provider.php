<?php

namespace SocialiteProviders\AmoCRM;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'AMOCRM';

    /**
     * Get the base URL.
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        return sprintf('https://www.amocrm.%s', $this->getTld());
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['tld'];
    }

    /**
     * Get the TLD config value.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getTld()
    {
        $tld = strtolower(ltrim($this->getConfig('tld', 'ru'), '.'));

        if (! in_array($tld, ['com', 'ru'], true)) {
            throw new InvalidArgumentException('Invalid TLD value.');
        }

        return $tld;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase("{$this->getBaseUrl()}/oauth", $state);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenUrl()
    {
        $domain = $this->request->get('referer');

        return "https://{$domain}/oauth2/access_token";
    }

    /**
     * {@inheritDoc}
     */
    protected function getUserByToken($token)
    {
        $domain = $this->request->get('referer');

        $response = $this->getHttpClient()->get("https://$domain/api/v4/account", [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        $account = json_decode((string) $response->getBody(), true);

        $response = $this->getHttpClient()->get("https://$domain/api/v4/users/{$account['current_user_id']}", [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'name'     => $user['name'],
            'nickname' => $user['email'],
            'email'    => $user['email'],
        ]);
    }
}
