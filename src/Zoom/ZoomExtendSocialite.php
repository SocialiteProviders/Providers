<?php

namespace SocialiteProviders\Zoom;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZoomExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('zoom', Provider::class);
    }
}
