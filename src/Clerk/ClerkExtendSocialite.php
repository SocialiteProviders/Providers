<?php

namespace SocialiteProviders\Clerk;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ClerkExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('clerk', Provider::class);
    }
}
