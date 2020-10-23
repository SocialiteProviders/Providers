<?php

namespace SocialiteProviders\Yiban;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YibanExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('yiban', Provider::class);
    }
}
