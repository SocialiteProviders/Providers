<?php

namespace SocialiteProviders\Vtex;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VtexExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('vtex', Provider::class);
    }
}
