<?php

namespace SocialiteProviders\Wonderlist;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WonderlistExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'wonderlist',
            __NAMESPACE__.'\Provider'
        );
    }
}
