<?php

namespace SocialiteProviders\Opskins;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OpskinsExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('opskins', __NAMESPACE__ . '\Provider');
    }
}
