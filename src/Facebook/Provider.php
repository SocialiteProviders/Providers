<?php

namespace SocialiteProviders\Facebook;

use Laravel\Socialite\Two\FacebookProvider;
use SocialiteProviders\Manager\ConfigTrait;

class Provider extends FacebookProvider
{
    use ConfigTrait;

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'FACEBOOK';
}