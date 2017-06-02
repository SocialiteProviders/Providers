<?php

namespace SocialiteProviders\projectv;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ProjectvExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'projectv', __NAMESPACE__.'\Provider'
        );
    }
}
