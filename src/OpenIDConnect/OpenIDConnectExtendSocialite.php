<?php

namespace SocialiteProviders\OpenIDConnect;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OpenIDConnectExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('openidconnect', Provider::class);
    }
}
