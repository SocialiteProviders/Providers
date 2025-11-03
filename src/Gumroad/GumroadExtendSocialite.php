<?php

namespace SocialiteProviders\Gumroad;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GumroadExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('gumroad', Provider::class);
    }
}
