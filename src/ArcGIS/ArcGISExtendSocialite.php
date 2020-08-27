<?php

namespace SocialiteProviders\ArcGIS;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ArcGISExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('arcgis', Provider::class);
    }
}
