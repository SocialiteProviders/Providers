<?php

namespace SocialiteProviders\Starling;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StarlingExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('starling', Provider::class);
    }
}
