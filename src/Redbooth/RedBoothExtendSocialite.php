<?php

namespace SocialiteProviders\Redbooth;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RedBoothExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('redbooth', Provider::class);
    }
}
