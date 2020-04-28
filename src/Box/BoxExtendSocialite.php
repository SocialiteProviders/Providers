<?php

namespace SocialiteProviders\Box;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BoxExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'box',
            __NAMESPACE__.'\Provider'
        );
    }
}
