<?php

namespace SocialiteProviders\Faceit;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FaceitExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('faceit', Provider::class);
    }
}
