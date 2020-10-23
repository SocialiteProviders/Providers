<?php

namespace SocialiteProviders\Ovh;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OvhExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('ovh', Provider::class);
    }
}
