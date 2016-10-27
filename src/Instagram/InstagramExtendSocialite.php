<?php

namespace SocialiteProviders\Instagram;

use SocialiteProviders\Manager\SocialiteWasCalled;

class InstagramExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'instagram', __NAMESPACE__.'\Provider'
        );
    }
}
