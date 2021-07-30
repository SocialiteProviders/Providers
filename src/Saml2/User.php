<?php

namespace SocialiteProviders\Saml2;

use _HumbugBox58fd4d9e2a25\Assert\Assertion;
use Laravel\Socialite\AbstractUser;
use LightSaml\Model\Assertion\Assertion;

class User extends AbstractUser
{
    protected $assertion;

    public function getAssertion(): Assertion
    {
        return $this->assertion;
    }

    public function setAssertion(Assertion $assertion): User
    {
        $this->assertion = $assertion;

        return $this;
    }
}
