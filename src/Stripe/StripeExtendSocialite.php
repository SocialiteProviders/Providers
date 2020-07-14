<?php

namespace SocialiteProviders\Stripe;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StripeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('stripe', Provider::class);
    }
}
