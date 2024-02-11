<?php

namespace SocialiteProviders\Eveonline;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EveonlineExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('eveonline', Provider::class);
    }
}
