<?php

namespace SocialiteProviders\PeeringDB;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PeeringDBExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('peeringdb', Provider::class);
    }
}
