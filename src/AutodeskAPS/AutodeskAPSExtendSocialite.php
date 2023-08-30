<?php

namespace SocialiteProviders\AutodeskAPS;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AutodeskAPSExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('aps', Provider::class);
    }
}
