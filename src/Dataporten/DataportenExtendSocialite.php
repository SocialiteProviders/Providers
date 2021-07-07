<?php

namespace SocialiteProviders\Dataporten;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DataportenExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('dataporten', Provider::class);
    }
}
