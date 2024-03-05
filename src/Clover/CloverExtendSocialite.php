<?php

namespace SocialiteProviders\Clover;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CloverExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite(Provider::IDENTIFIER, Provider::class);
    }
}
