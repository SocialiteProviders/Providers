<?php

namespace SocialiteProviders\Untappd;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UntappdExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('untappd', Provider::class);
    }
}
