<?php

namespace SocialiteProviders\Flexkids;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FlexkidsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('flexkids', Provider::class);
    }
}
