<?php

namespace SocialiteProviders\Kit;

use SocialiteProviders\Manager\SocialiteWasCalled;

class KitExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('kit', Provider::class);
    }
}
