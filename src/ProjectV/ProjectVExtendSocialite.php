<?php

namespace SocialiteProviders\ProjectV;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ProjectVExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'projectv',
            __NAMESPACE__.'\Provider'
        );
    }
}
