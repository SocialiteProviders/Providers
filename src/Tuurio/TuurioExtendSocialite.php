<?php

namespace SocialiteProviders\Tuurio;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TuurioExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('tuurio', Provider::class);
    }
}
