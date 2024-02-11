<?php

namespace SocialiteProviders\Aweber;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AweberExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('aweber', Provider::class, Server::class);
    }
}
