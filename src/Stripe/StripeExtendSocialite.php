<?php

namespace SocialiteProviders\Stripe;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StripeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('stripe', Provider::class);
    }
}
