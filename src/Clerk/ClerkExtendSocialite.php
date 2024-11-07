<?php

namespace SocialiteProviders\Clerk;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ClerkExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('clerk', Provider::class);
    }
}
