<?php

namespace SocialiteProviders\Acclaim;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AcclaimExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('acclaim', Provider::class);
    }
}
