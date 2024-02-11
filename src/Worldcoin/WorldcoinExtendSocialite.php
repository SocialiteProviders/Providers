<?php

namespace SocialiteProviders\Worldcoin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WorldcoinExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('worldcoin', Provider::class);
    }
}
