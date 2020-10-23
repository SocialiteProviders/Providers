<?php

namespace SocialiteProviders\Sage;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SageExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('sage', Provider::class);
    }
}
