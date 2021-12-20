<?php

namespace SocialiteProviders\Monday;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MondayExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('monday', Provider::class);
    }
}
