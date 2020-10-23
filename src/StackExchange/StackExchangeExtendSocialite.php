<?php

namespace SocialiteProviders\StackExchange;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StackExchangeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('stackexchange', Provider::class);
    }
}
