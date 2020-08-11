<?php

namespace SocialiteProviders\VersionOne;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VersionOneExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('versionone', Provider::class);
    }
}
