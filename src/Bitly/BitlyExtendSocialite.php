<?php

namespace SocialiteProviders\Bitly;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BitlyExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('bitly', Provider::class);
    }
}
