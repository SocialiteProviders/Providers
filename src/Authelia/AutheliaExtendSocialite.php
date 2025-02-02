<?php

namespace SocialiteProviders\Authelia;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AutheliaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('authelia', Provider::class);
    }
}
