<?php

namespace SocialiteProviders\Line;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LineExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'line', __NAMESPACE__.'\Provider'
        );
    }
}
