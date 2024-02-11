<?php

namespace SocialiteProviders\Deviantart;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DeviantartExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('deviantart', Provider::class);
    }
}
