<?php

namespace SocialiteProviders\Etsy;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EtsyExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('etsy', Provider::class);
    }
}
