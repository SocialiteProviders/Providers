<?php

namespace SocialiteProviders\Keycloak;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'KEYCLOAK';

    protected $scopeSeparator = ' ';

    protected $scopes = ['openid'];

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['base_url', 'realms'];
    }

    protected function getBaseUrl()
    {
        return rtrim(rtrim($this->getConfig('base_url'), '/').'/realms/'.$this->getConfig('realms', 'master'), '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/protocol/openid-connect/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/protocol/openid-connect/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl().'/protocol/openid-connect/userinfo', [
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
        return (new User())->setRaw($user)->map([
            'id'        => Arr::get($user, 'sub'),
            'nickname'  => Arr::get($user, 'preferred_username'),
            'name'      => Arr::get($user, 'name'),
            'email'     => Arr::get($user, 'email'),
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

    /**
     * Return logout endpoint with redirect_uri query parameter.
     *
     * @param string|null $redirectUri
     *
     * @return string
     */
    public function getLogoutUrl(?string $redirectUri = null): string
    {
        $logoutUrl = $this->getBaseUrl().'/protocol/openid-connect/logout';

        if ($redirectUri === null) {
            return $logoutUrl;
        }

        return $logoutUrl.'?redirect_uri='.urlencode($redirectUri);
    }
}
