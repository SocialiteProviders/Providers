<?php

namespace SocialiteProviders\FusionAuth;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FusionAuthExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('fusionauth', Provider::class);
    }
}
