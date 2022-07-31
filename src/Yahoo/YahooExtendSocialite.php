<?php

namespace SocialiteProviders\Yahoo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YahooExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('yahoo', Provider::class);
    }
}
