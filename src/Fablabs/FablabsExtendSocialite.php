<?php

namespace SocialiteProviders\Fablabs;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FablabsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('fablabs', Provider::class);
    }
}
