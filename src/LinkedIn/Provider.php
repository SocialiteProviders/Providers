<?php

namespace SocialiteProviders\LinkedIn;

use Laravel\Socialite\Two\LinkedInProvider;
use SocialiteProviders\Manager\ConfigTrait;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;

class Provider extends LinkedInProvider implements ProviderInterface
{
    use ConfigTrait;

    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'LINKEDIN';
}
