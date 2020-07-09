<?php

namespace SocialiteProviders\PayPalSandbox;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PayPalSandboxExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('paypal_sandbox', Provider::class);
    }
}
