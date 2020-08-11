<?php

namespace SocialiteProviders\Redbooth;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RedBoothExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('redbooth', Provider::class);
    }
}
