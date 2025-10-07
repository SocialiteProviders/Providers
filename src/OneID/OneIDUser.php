<?php

namespace Aslnbxrz\OneID;

use SocialiteProviders\Manager\OAuth2\User as OAuth2User;

class OneIDUser extends OAuth2User
{
    /** Return PINFL (citizen ID) */
    public function getPinfl(): ?string
    {
        return $this->attributes['pinfl'] ?? null;
    }

    /** Return OneID session id */
    public function getSessionId(): ?string
    {
        return $this->attributes['sess_id'] ?? null;
    }

    /** Return passport number */
    public function getPassport(): ?string
    {
        return $this->attributes['passport'] ?? null;
    }

    /** Return normalized phone */
    public function getPhone(): ?string
    {
        return $this->attributes['phone'] ?? null;
    }

    /** Lightweight gender guess based on PINFL first digit (if numeric) */
    public function getGender(): ?string
    {
        $pin = $this->getPinfl();
        if (empty($pin) || !ctype_digit($pin)) {
            return null; // or 'unknown'
        }
        // Odd => male, Even => female
        return ((int)$pin[0]) % 2 ? 'male' : 'female';
    }
}