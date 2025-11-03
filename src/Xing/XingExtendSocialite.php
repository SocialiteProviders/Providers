<?php

namespace SocialiteProviders\Xing;

use SocialiteProviders\Manager\SocialiteWasCalled;

class XingExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('xing', Provider::class, Server::class);
    }
}
