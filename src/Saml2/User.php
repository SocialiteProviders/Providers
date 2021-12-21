<?php

namespace SocialiteProviders\Saml2;

use Laravel\Socialite\AbstractUser;
use LightSaml\Model\Assertion\Assertion;

class User extends AbstractUser
{
    protected $assertion;

    /**
     * The user's first name.
     *
     * @var string|null
     */
    public $first_name;

    /**
     * The user's last name.
     *
     * @var string|null
     */
    public $last_name;

    /**
     * The user's UPN (user principal name).
     *
     * @var string|null
     */
    public $upn;

    public function getAssertion(): Assertion
    {
        return $this->assertion;
    }

    public function setAssertion(Assertion $assertion): User
    {
        $this->assertion = $assertion;

        return $this;
    }

    /**
     * Get the first name of the user.
     *
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Get the last name of the user.
     *
     * @return string|null
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Get the UPN of the user.
     *
     * @return string|null
     */
    public function getUpn()
    {
        return $this->upn;
    }
}
