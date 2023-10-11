<?php

namespace SocialiteProviders\Neto;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NetoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('neto', Provider::class);
    }
}
