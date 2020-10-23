<?php

namespace SocialiteProviders\ClaveUnica;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ClaveUnicaExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('claveunica', Provider::class);
    }
}
