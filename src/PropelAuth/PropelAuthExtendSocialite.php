<?php

namespace SocialiteProviders\PropelAuth;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PropelAuthExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('propelauth', Provider::class);
    }
}
