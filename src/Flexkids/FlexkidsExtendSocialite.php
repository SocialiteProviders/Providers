<?php

namespace SocialiteProviders\Flexkids;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FlexkidsExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('flexkids', __NAMESPACE__.'\Provider');
    }
}
