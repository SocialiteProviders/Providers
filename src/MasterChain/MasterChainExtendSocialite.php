<?php

namespace SocialiteProviders\MasterChain;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MasterChainExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'masterchain',
            __NAMESPACE__.'\Provider'
        );
    }
}
