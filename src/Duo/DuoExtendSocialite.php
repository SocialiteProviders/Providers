<?php

namespace SocialiteProviders\Duo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DuoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('duo', Provider::class);
    }
}
