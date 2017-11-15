<?php

namespace SocialiteProviders\Admitad;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AdmitadExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('admitad', __NAMESPACE__.'\Provider');
    }
}
