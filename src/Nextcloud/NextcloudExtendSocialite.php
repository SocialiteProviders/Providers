<?php

namespace SocialiteProviders\Nextcloud;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NextcloudExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('nextcloud', Provider::class);
    }
}
