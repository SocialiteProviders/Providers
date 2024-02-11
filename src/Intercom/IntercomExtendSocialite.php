<?php

namespace SocialiteProviders\Intercom;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IntercomExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('intercom', Provider::class);
    }
}
