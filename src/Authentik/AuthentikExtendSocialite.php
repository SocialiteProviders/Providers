<?php

namespace SocialiteProviders\Authentik;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AuthentikExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('authentik', Provider::class);
    }
}
