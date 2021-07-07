<?php

namespace SocialiteProviders\Intercom;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IntercomExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('intercom', Provider::class);
    }
}
