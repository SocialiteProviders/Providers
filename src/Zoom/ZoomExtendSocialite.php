<?php

namespace SocialiteProviders\Zoom;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZoomExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('zoom', Provider::class);
    }
}
