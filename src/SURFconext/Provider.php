<?php

namespace SocialiteProviders\SURFconext;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    const IDENTIFIER = 'SURFconext';

    protected $scopes = ['openid'];

    /**
     * @see https://oidc.surfconext.nl/.well-known/openid-configuration
     * @see https://oidc.test.surfconext.nl/.well-known/openid-configuration
     *
     * @return string
     */
    protected function getHostname()
    {
        if ($this->getConfig('test')) {
            return 'oidc.test.surfconext.nl';
        }

        return 'oidc.surfconext.nl';
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

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * https://wiki.surfnet.nl/pages/viewpage.action?pageId=10125750
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'                            => Arr::get($user, 'sub'),
            'name'                          => Arr::get($user, 'name'),
            'nickname'                      => Arr::get($user, 'nickname'),
            'email'                         => Arr::get($user, 'email'),
            'avatar'                        => Arr::get($user, 'picture'),
            'sub'                           => Arr::get($user, 'sub'),
            'preferred_username'            => Arr::get($user, 'preferred_username'),
            'given_name'                    => Arr::get($user, 'given_name'),
            'family_name'                   => Arr::get($user, 'family_name'),
            'schac_home_organization'       => Arr::get($user, 'schac_home_organization'),
            'schac_home_organization_type'  => Arr::get($user, 'schac_home_organization_type'),
            'edu_person_affiliations'       => Arr::get($user, 'edu_person_affiliations'),
            'edu_person_scoped_affiliations'=> Arr::get($user, 'edu_person_scoped_affiliations'),
            'edu_person_targeted_id'        => Arr::get($user, 'edu_person_targeted_id'),
            'uids'                          => Arr::get($user, 'uids'),
            'schac_personal_unique_codes'   => Arr::get($user, 'schac_personal_unique_codes'),
            'edu_person_principal_name'     => Arr::get($user, 'edu_person_principal_name'),
            'edu_person_entitlements'       => Arr::get($user, 'edu_person_entitlements'),
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

    public static function additionalConfigKeys()
    {
        return ['test'];
    }
}
