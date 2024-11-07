<?php

namespace SocialiteProviders\Nuvemshop;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NuvemshopExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('nuvemshop', Provider::class);
    }
}
