<?php

namespace SocialiteProviders\Mollie;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MollieExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mollie', Provider::class);
    }
}
