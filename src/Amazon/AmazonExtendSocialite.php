<?php

namespace SocialiteProviders\Amazon;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AmazonExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('amazon', Provider::class);
    }
}
