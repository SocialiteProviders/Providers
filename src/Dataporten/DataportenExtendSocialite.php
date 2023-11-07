<?php

namespace SocialiteProviders\Dataporten;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DataportenExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('dataporten', Provider::class);
    }
}
