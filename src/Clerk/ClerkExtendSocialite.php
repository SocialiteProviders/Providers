<?php

namespace SocialiteProviders\Clerk;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ClerkExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('clerk', Provider::class);
    }
}
