<?php

namespace SocialiteProviders\Flexkids;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FlexkidsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('flexkids', Provider::class);
    }
}
