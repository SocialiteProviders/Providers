<?php

namespace SocialiteProviders\LinkedInOpenId;

use Laravel\Socialite\Two\LinkedInOpenIdProvider;
use SocialiteProviders\Manager\ConfigTrait;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;

class Provider extends LinkedInOpenIdProvider implements ProviderInterface
{
    use ConfigTrait;

    public const IDENTIFIER = 'LINKEDINOPENID';
}
