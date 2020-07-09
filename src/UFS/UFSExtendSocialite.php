<?php

namespace SocialiteProviders\UFS;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UFSExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('ufs', Provider::class);
    }
}
