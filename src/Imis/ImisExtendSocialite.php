<?php

namespace SocialiteProviders\Imis;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ImisExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('imis', Provider::class);
    }
}
