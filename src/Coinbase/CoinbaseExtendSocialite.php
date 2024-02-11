<?php

namespace SocialiteProviders\Coinbase;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CoinbaseExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('coinbase', Provider::class);
    }
}
