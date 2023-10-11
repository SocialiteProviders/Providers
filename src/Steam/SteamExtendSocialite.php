<?php

namespace SocialiteProviders\Steam;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SteamExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('steam', Provider::class);
    }
}
