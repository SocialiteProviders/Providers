<?php

namespace SocialiteProviders\Xbox;

use SocialiteProviders\Manager\SocialiteWasCalled;

class XboxExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('xbox', Provider::class);
    }
}
