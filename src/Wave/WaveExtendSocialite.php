<?php

namespace SocialiteProviders\Wave;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WaveExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('wave', Provider::class);
    }
}
