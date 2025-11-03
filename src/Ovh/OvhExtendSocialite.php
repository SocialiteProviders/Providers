<?php

namespace SocialiteProviders\Ovh;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OvhExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('ovh', Provider::class);
    }
}
