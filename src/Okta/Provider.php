<?php

namespace SocialiteProviders\Okta;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'OKTA';

    /**
     * Scopes defintions.
     *
     * @see https://developer.okta.com/docs/reference/api/oidc/#scopes
     */
    public const SCOPE_OPENID = 'openid';
    public const SCOPE_PROFILE = 'profile';
    public const SCOPE_EMAIL = 'email';
    public const SCOPE_ADDRESS = 'address';
    public const SCOPE_PHONE = 'phone';
    public const SCOPE_OFFLINE_ACCESS = 'offline_access';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        self::SCOPE_OPENID,
        self::SCOPE_PROFILE,
        self::SCOPE_EMAIL,
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    protected function getOktaUrl()
    {
        return $this->getConfig('base_url');
    }

    /**
     * Returns the Auth Server ID based on config option 'auth_server_id'.
     *
     * @return string
     */
    protected function getAuthServerId()
    {
        $auth_server_id = $this->getConfig('auth_server_id', null);

        if ($auth_server_id) {
            return $auth_server_id.'/';
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['base_url', 'auth_server_id'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getOktaUrl().'/oauth2/'.$this->getAuthServerId().'v1/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getOktaUrl().'/oauth2/'.$this->getAuthServerId().'/v1/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getOktaUrl().'/oauth2/'.$this->getAuthServerId().'v1/userinfo', [
            'headers' => [
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
            'id'             => Arr::get($user, 'sub'),
            'email'          => Arr::get($user, 'email'),
            'email_verified' => Arr::get($user, 'email_verified', false),
            'nickname'       => Arr::get($user, 'nickname'),
            'name'           => Arr::get($user, 'name'),
            'first_name'     => Arr::get($user, 'given_name'),
            'last_name'      => Arr::get($user, 'family_name'),
            'profileUrl'     => Arr::get($user, 'profile'),
            'address'        => Arr::get($user, 'address'),
            'phone'          => Arr::get($user, 'phone'),
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
