<?php

namespace SocialiteProviders\LinkedIn;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LinkedInExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'linkedin', __NAMESPACE__.'\Provider'
        );
    }
}
