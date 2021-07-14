<?php

namespace SocialiteProviders\SURFconext;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SURFconext';

    protected $scopes = ['openid'];

    /**
     * @see https://connect.surfconext.nl/.well-known/openid-configuration
     * @see https://connect.test.surfconext.nl/.well-known/openid-configuration
     *
     * @return string
     */
    protected function getHostname()
    {
        if ($this->getConfig('test')) {
            return 'connect.test.surfconext.nl/oidc';
        }

        return 'connect.surfconext.nl/oidc';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://'.$this->getHostname().'/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://'.$this->getHostname().'/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://'.$this->getHostname().'/userinfo',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * https://wiki.surfnet.nl/pages/viewpage.action?pageId=10125750
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'                           => Arr::get($user, 'sub'),
            'name'                         => Arr::get($user, 'name'),
            'nickname'                     => Arr::get($user, 'nickname'),
            'email'                        => Arr::get($user, 'email'),
            'avatar'                       => Arr::get($user, 'picture'),
            'sub'                          => Arr::get($user, 'sub'),
            'preferred_username'           => Arr::get($user, 'preferred_username'),
            'given_name'                   => Arr::get($user, 'given_name'),
            'family_name'                  => Arr::get($user, 'family_name'),
            'schac_home_organization'      => Arr::get($user, 'schac_home_organization'),
            'schac_home_organization_type' => Arr::get($user, 'schac_home_organization_type'),
            'eduperson_affiliation'        => Arr::get($user, 'eduperson_affiliation'),
            'eduperson_scoped_affiliation' => Arr::get($user, 'eduperson_scoped_affiliation'),
            'eduperson_targeted_id'        => Arr::get($user, 'eduperson_targeted_id'),
            'uids'                         => Arr::get($user, 'uids'),
            'schac_personal_unique_code'   => Arr::get($user, 'schac_personal_unique_code'),
            'eduperson_principal_name'     => Arr::get($user, 'eduperson_principal_name'),
            'eduperson_entitlement'        => Arr::get($user, 'eduperson_entitlement'),
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
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['test'];
    }
}
