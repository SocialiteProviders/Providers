<?php

namespace SocialiteProviders\Imis;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ImisExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('imis', Provider::class);
    }
}
