<?php

namespace SocialiteProviders\Trakt;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TraktExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('trakt', Provider::class);
    }
}
