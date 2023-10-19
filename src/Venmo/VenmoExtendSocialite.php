<?php

namespace SocialiteProviders\Venmo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VenmoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('venmo', Provider::class);
    }
}
