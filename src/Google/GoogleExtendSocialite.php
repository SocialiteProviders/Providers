<?php

namespace SocialiteProviders\Google;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GoogleExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('google', Provider::class);
    }
}
