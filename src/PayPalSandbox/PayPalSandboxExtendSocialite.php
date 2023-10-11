<?php

namespace SocialiteProviders\PayPalSandbox;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PayPalSandboxExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('paypal_sandbox', Provider::class);
    }
}
