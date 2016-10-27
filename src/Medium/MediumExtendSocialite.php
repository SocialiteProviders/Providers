<?php

namespace SocialiteProviders\Medium;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MediumExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'medium', __NAMESPACE__.'\Provider'
        );
    }
}
