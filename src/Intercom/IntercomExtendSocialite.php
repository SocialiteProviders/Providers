<?php

namespace SocialiteProviders\Intercom;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IntercomExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('intercom', Provider::class);
    }
}
