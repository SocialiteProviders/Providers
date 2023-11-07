<?php

namespace SocialiteProviders\Subscribestar;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SubscribestarExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('subscribestar', Provider::class);
    }
}
