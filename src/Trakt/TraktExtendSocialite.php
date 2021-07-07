<?php

namespace SocialiteProviders\Trakt;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TraktExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('trakt', Provider::class);
    }
}
