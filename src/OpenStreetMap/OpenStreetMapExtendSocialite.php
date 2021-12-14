<?php

namespace SocialiteProviders\OpenStreetMap;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OpenStreetMapExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('openstreetmap', Provider::class);
    }
}
