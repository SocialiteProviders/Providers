<?php

namespace SocialiteProviders\PixelTools;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PixelToolsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('pixeltools', Provider::class);
    }
}