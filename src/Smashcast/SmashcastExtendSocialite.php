<?php

namespace SocialiteProviders\Smashcast;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SmashcastExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('hitbox', __NAMESPACE__.'\Provider');
    }
}
