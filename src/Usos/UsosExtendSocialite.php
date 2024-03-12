<?php

namespace SocialiteProviders\Usos;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UsosExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('usos', Provider::class, Server::class);
    }
}
