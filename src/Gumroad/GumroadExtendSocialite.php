<?php

namespace SocialiteProviders\Gumroad;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GumroadExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('gumroad', __NAMESPACE__.'\Provider');
    }
}
