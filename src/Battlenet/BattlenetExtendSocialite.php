<?php

namespace SocialiteProviders\Battlenet;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BattlenetExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('battlenet', Provider::class);
    }
}
