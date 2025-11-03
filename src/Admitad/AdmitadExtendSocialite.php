<?php

namespace SocialiteProviders\Admitad;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AdmitadExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('admitad', Provider::class);
    }
}
