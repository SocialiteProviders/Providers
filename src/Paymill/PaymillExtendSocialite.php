<?php

namespace SocialiteProviders\Paymill;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PaymillExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('paymill', Provider::class);
    }
}
