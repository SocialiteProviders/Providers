<?php

namespace SocialiteProviders\Adobe;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AdobeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('adobe', Provider::class);
    }
}
