<?php

namespace SocialiteProviders\IFSP;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IFSPExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('ifsp', Provider::class);
    }
}
