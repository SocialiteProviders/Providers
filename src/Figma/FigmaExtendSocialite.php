<?php

namespace SocialiteProviders\Figma;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FigmaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('figma', Provider::class);
    }
}
