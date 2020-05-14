<?php

namespace SocialiteProviders\Wonderlist;

use SocialiteProviders\Manager\SocialiteWasCalled;

/**
 * @deprecated Wunderlist is shutting down
 * @see        https://www.wunderlist.com/blog/goodbye-from-wunderlist/
 */
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
