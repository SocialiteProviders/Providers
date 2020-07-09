<?php

namespace SocialiteProviders\Cheddar;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CheddarExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('cheddar', Provider::class);
    }
}
