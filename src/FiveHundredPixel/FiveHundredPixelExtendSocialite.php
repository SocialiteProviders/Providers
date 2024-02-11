<?php

namespace SocialiteProviders\FiveHundredPixel;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FiveHundredPixelExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('500px', Provider::class, Server::class);
    }
}
