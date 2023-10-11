<?php

namespace SocialiteProviders\Lichess;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LichessExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('lichess', Provider::class);
    }
}
