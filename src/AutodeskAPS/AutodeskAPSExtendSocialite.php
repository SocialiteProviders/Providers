<?php

namespace SocialiteProviders\AutodeskAPS;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AutodeskAPSExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('aps', Provider::class);
    }
}
