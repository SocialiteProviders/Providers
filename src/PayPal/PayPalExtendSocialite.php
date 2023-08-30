<?php

namespace SocialiteProviders\PayPal;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PayPalExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('paypal', Provider::class);
    }
}
