<?php

namespace SocialiteProviders\Box;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BoxExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('box', Provider::class);
    }
}
