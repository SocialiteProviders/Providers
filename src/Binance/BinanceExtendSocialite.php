<?php

namespace SocialiteProviders\Binance;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BinanceExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('binance', Provider::class);
    }
}
