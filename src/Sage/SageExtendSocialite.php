<?php

namespace SocialiteProviders\Sage;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SageExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('sage', Provider::class);
    }
}
