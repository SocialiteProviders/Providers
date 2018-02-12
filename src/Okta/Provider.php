<?php

namespace SocialiteProviders\Okta;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'OKTA';

    /**
     * Scopes defintions.
     *
     * @see http://developer.okta.com/docs/api/resources/oidc.html#scopes
     */
    const SCOPE_OPENID = 'openid';
    const SCOPE_PROFILE = 'profile';
    const SCOPE_EMAIL = 'email';
    const SCOPE_ADDRESS = 'address';
    const SCOPE_PHONE = 'phone';
    const SCOPE_OFFLINE_ACCESS = 'offline_access';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
        'profile',
        'email',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getOktaUrl()
    {
        return $this->getConfig('base_url');
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['base_url'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getOktaUrl().'/oauth2/v1/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getOktaUrl().'/oauth2/v1/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getOktaUrl().'/oauth2/v1/userinfo', [
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
            'id'             => array_get($user, 'sub'),
            'email'          => array_get($user, 'email'),
            'email_verified' => array_get($user, 'email_verified', false),
            'nickname'       => array_get($user, 'nickname'),
            'name'           => array_get($user, 'name'),
            'first_name'     => array_get($user, 'given_name'),
            'last_name'      => array_get($user, 'family_name'),
            'profileUrl'     => array_get($user, 'profile'),
            'address'        => array_get($user, 'address'),
            'phone'          => array_get($user, 'phone'),
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
