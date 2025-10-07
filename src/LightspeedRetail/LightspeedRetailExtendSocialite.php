<?php

namespace SocialiteProviders\LightspeedRetail;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LightspeedRetailExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     * @return void
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('lightspeedretail', Provider::class);
    }
}
