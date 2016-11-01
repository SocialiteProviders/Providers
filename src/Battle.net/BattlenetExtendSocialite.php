<?php

namespace SocialiteProviders\Battlenet;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BattlenetExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'battlenet', __NAMESPACE__.'\Provider'
        );
    }
}
