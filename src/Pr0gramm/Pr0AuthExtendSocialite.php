<?php

namespace SocialiteProviders\Pr0Auth;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Pr0AuthExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('pr0gramm', Provider::class);
    }
}
