<?php

namespace SocialiteProviders\Line;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LineExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('line', Provider::class);
    }
}
