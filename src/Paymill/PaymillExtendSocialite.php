<?php

namespace SocialiteProviders\Paymill;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PaymillExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('paymill', Provider::class);
    }
}
