<?php

namespace SocialiteProviders\Venmo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VenmoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('venmo', Provider::class);
    }
}
