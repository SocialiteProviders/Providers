<?php

namespace SocialiteProviders\PayPal;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PayPalExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('paypal', Provider::class);
    }
}
