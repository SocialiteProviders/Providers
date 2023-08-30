<?php

namespace SocialiteProviders\Starling;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StarlingExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('starling', Provider::class);
    }
}
