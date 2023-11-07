<?php

namespace SocialiteProviders\Yahoo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YahooExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('yahoo', Provider::class);
    }
}
