<?php

namespace SocialiteProviders\Smashcast;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SmashcastExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('smashcast', Provider::class);
    }
}
