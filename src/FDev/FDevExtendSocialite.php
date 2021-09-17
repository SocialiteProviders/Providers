<?php

namespace SocialiteProviders\FDev;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FDevExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('fdev', Provider::class);
    }
}
