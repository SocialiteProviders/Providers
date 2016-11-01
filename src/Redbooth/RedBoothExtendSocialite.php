<?php

namespace SocialiteProviders\Redbooth;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RedboothExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('redbooth', __NAMESPACE__.'\Provider');
    }
}
