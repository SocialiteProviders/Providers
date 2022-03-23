<?php

namespace SocialiteProviders\Azure;

use SocialiteProviders\Manager\OAuth2\User as oAuth2User;

class User extends oAuth2User
{
    /**
     * The user's principal name.
     *
     * @var string
     */
    public $principalName;

    /**
     * Get the principal name for the user.
     *
     * @return string
     */
    public function getPrincipalName()
    {
        return $this->principalName;
    }
}
