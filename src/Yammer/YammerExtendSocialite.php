<?php

namespace SocialiteProviders\Yammer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YammerExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('yammer', Provider::class);
    }
}
