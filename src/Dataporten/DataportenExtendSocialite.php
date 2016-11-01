<?php

namespace SocialiteProviders\Dataporten;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DataportenExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('dataporten', __NAMESPACE__.'\Provider');
    }
}
