<?php

namespace SocialiteProviders\Salla;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SallaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('salla', Provider::class);
    }
}