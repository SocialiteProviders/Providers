<?php

namespace SocialiteProviders\Identity;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IdentityExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'identity', __NAMESPACE__.'\Provider'
        );
    }
}
