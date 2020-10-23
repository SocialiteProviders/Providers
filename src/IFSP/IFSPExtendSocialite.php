<?php

namespace SocialiteProviders\IFSP;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IFSPExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('ifsp', Provider::class);
    }
}
