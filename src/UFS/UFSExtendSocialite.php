<?php

namespace SocialiteProviders\UFS;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UFSExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('ufs', Provider::class);
    }
}
