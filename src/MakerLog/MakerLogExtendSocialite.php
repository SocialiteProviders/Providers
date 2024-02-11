<?php

namespace SocialiteProviders\MakerLog;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MakerLogExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('makerlog', Provider::class);
    }
}
