<?php

namespace SocialiteProviders\ArcGIS;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ArcGISExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('arcgis', Provider::class);
    }
}
