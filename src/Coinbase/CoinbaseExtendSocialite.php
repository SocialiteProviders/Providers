<?php

namespace SocialiteProviders\Coinbase;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CoinbaseExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'coinbase', __NAMESPACE__.'\Provider'
        );
    }
}
