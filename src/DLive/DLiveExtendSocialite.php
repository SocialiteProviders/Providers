<?php

namespace SocialiteProviders\DLive;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DLiveExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('dlive', __NAMESPACE__.'\Provider');
    }
}
