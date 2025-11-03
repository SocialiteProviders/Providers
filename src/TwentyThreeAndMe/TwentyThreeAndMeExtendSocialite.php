<?php

namespace SocialiteProviders\TwentyThreeAndMe;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TwentyThreeAndMeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('23andme', Provider::class);
    }
}
