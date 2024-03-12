<?php

namespace SocialiteProviders\Vatsim;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VatsimExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('vatsim', Provider::class);
    }
}
