<?php

namespace SocialiteProviders\Azure;

use SocialiteProviders\Manager\OAuth2\User as oAuth2User;

/**
 * @deprecated Use the Microsoft provider (socialiteproviders/microsoft) instead.
 * @see        https://github.com/SocialiteProviders/Microsoft-Azure#migrating-to-the-microsoft-provider
 */
class User extends oAuth2User
{
    /**
     * The user's principal name.
     *
     * @var string
     */
    public $principalName;

    /**
     * The user's mail.
     *
     * @var string
     */
    public $mail;

    /**
     * Get the principal name for the user.
     *
     * @return string
     */
    public function getPrincipalName()
    {
        return $this->principalName;
    }

    /**
     * Get the mail for the user.
     *
     * @return string
     */
    public function getMail()
    {
        return $this->mail;
    }
}
