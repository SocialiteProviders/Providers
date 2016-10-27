<?php

namespace SocialiteProviders\Podio;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PodioExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'podio', __NAMESPACE__.'\Provider'
        );
    }
}
