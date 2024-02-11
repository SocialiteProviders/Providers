<?php

namespace SocialiteProviders\OSChina;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OSChinaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('oschina', Provider::class);
    }
}
