<?php

namespace SocialiteProviders\EyeEm;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EyeEmExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('eyeem', Provider::class);
    }
}
