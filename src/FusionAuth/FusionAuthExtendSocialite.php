<?php

namespace SocialiteProviders\FusionAuth;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FusionAuthExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('fusionauth', Provider::class);
    }
}
