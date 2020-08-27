<?php

namespace SocialiteProviders\SURFconext;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SURFconextExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('surfconext', Provider::class);
    }
}
