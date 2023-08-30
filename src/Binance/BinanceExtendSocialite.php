<?php

namespace SocialiteProviders\Binance;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BinanceExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('binance', Provider::class);
    }
}
