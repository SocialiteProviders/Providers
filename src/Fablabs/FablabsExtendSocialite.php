<?php

namespace SocialiteProviders\Fablabs;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FablabsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('fablabs', Provider::class);
    }
}
