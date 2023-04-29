<?php

namespace SocialiteProviders\Google;

use Laravel\Socialite\Two\GoogleProvider;
use SocialiteProviders\Manager\ConfigTrait;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;

class Provider extends GoogleProvider implements ProviderInterface
{
    use ConfigTrait;

    public const IDENTIFIER = 'GOOGLE';
}
