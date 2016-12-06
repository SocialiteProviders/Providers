<?php

namespace SocialiteProviders\Jira;

use SocialiteProviders\Manager\OAuth1\User;
use SocialiteProviders\Manager\OAuth1\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'JIRA';

     /**
      * {@inheritdoc}
      */
     protected function mapUserToObject(array $user)
     {
         $userObject = new User();

         if (isset($user['extra'])) {
             $userObject = $userObject->setRaw($user['extra']);
         }

         return $userObject->map([
             'id' => array_get($user, 'key'),
             'nickname' => array_get($user, 'nickname', array_get($user, 'name')),
             'name' => array_get($user, 'displayName', array_get($user, 'name')),
             'email' => array_get($user, 'emailAddress', array_get($user, 'email')),
             'avatar' => array_get($user, 'avatarUrls.48x48', array_get($user, 'avatar')),
             'active' => array_get($user, 'active'),
             'timezone' => array_get($user, 'timeZone'),
             'locale' => array_get($user, 'locale'),
         ]);

         return $userObject;
     }

    public static function additionalConfigKeys()
    {
        return ['base_uri', 'cert_path'];
    }
}
