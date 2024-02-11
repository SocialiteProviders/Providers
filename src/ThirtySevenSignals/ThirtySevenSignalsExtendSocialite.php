<?php

namespace SocialiteProviders\ThirtySevenSignals;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ThirtySevenSignalsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('37signals', Provider::class);
    }
}
