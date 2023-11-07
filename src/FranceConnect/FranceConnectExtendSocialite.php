<?php

namespace SocialiteProviders\FranceConnect;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FranceConnectExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('franceconnect', Provider::class);
    }
}
