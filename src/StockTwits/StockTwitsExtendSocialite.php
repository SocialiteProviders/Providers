<?php

namespace SocialiteProviders\StockTwits;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StockTwitsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'stocktwits',
            __NAMESPACE__.'\Provider'
        );
    }
}
