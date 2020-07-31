<?php

namespace SocialiteProviders\Facebook;

use Laravel\Socialite\Two\FacebookProvider;
use SocialiteProviders\Manager\ConfigTrait;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;

class Provider extends FacebookProvider implements ProviderInterface
{
    use ConfigTrait;

    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'FACEBOOK';
}
