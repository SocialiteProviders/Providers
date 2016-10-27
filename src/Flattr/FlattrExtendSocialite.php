<?php

namespace SocialiteProviders\Flattr;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FlattrExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'flattr', __NAMESPACE__.'\Provider'
        );
    }
}
