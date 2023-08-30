<?php

namespace SocialiteProviders\Eveonline;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EveonlineExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('eveonline', Provider::class);
    }
}
