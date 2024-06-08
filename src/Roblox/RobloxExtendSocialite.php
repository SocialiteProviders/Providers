<?php

namespace SocialiteProviders\Roblox;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RobloxExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('roblox', Provider::class);
    }
}
