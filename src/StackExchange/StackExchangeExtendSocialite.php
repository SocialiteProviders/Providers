<?php

namespace SocialiteProviders\StackExchange;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StackExchangeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('stackexchange', Provider::class);
    }
}
