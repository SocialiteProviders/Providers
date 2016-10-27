<?php

namespace SocialiteProviders\Moves;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MovesExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('moves', __NAMESPACE__.'\Provider');
    }
}
