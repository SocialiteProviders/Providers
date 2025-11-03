<?php

namespace SocialiteProviders\SURFconext;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SURFconextExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('surfconext', Provider::class);
    }
}
