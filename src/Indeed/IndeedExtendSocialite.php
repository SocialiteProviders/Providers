<?php

namespace SocialiteProviders\Indeed;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IndeedExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('indeed', Provider::class);
    }
}
