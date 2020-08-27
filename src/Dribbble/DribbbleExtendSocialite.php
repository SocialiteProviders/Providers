<?php

namespace SocialiteProviders\Dribbble;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DribbbleExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('dribbble', Provider::class);
    }
}
