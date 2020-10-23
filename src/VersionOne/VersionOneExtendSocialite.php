<?php

namespace SocialiteProviders\VersionOne;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VersionOneExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('versionone', Provider::class);
    }
}
