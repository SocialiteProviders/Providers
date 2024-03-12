<?php

namespace SocialiteProviders\OpenStreetMap;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OpenStreetMapExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('openstreetmap', Provider::class);
    }
}
