<?php

namespace SocialiteProviders\Tidal;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TidalExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('tidal', Provider::class);
    }
}
