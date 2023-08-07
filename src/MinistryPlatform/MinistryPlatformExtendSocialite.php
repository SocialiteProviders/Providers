<?php

namespace SocialiteProviders\MinistryPlatform;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MinistryPlatformExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('ministryplatform', Provider::class);
    }
}
