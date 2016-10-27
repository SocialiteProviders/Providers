<?php

namespace SocialiteProviders\Uber;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UberExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'uber', __NAMESPACE__.'\Provider'
        );
    }
}
