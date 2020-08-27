<?php

namespace SocialiteProviders\Amazon;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AmazonExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('amazon', Provider::class);
    }
}
