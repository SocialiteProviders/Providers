<?php

namespace SocialiteProviders\Mollie;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MollieExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('mollie', Provider::class);
    }
}
