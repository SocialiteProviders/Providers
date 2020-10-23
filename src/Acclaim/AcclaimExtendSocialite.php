<?php

namespace SocialiteProviders\Acclaim;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AcclaimExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('acclaim', Provider::class);
    }
}
