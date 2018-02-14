<?php

namespace SocialiteProviders\Steem;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SteemExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('steem', __NAMESPACE__.'\Provider');
    }
}
