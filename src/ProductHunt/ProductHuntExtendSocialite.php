<?php

namespace SocialiteProviders\ProductHunt;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ProductHuntExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('producthunt', Provider::class);
    }
}
