<?php

namespace SocialiteProviders\GovBR;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GovBRExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('govbr', Provider::class);
    }
}
