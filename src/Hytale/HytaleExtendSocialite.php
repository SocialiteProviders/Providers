<?php

namespace SocialiteProviders\Hytale;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HytaleExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('hytale', Provider::class);
    }
}
