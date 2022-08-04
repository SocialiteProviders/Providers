<?php

namespace SocialiteProviders\Bexio;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BexioExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('bexio', Provider::class);
    }
}
