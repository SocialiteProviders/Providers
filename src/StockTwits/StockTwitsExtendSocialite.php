<?php

namespace SocialiteProviders\StockTwits;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StockTwitsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('stocktwits', Provider::class);
    }
}
