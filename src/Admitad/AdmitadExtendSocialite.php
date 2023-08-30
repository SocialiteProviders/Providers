<?php

namespace SocialiteProviders\Admitad;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AdmitadExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('admitad', Provider::class);
    }
}
