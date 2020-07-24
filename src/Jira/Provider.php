<?php

namespace SocialiteProviders\Jira;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'JIRA';


    protected function mapUserToObject(array $user)
    {
        $userObject = new User();

        if (isset($user['extra'])) {
            $userObject = $userObject->setRaw($user['extra']);
        }

        return $userObject->map([
            'id'       => Arr::get($user, 'key'),
            'nickname' => Arr::get($user, 'nickname', Arr::get($user, 'name')),
            'name'     => Arr::get($user, 'displayName', Arr::get($user, 'name')),
            'email'    => Arr::get($user, 'emailAddress', Arr::get($user, 'email')),
            'avatar'   => Arr::get($user, 'avatarUrls.48x48', Arr::get($user, 'avatar')),
            'active'   => Arr::get($user, 'active'),
            'timezone' => Arr::get($user, 'timeZone'),
            'locale'   => Arr::get($user, 'locale'),
        ]);
    }

    public static function additionalConfigKeys()
    {
        return ['base_uri', 'cert_path', 'cert_passphrase'];
    }
}
