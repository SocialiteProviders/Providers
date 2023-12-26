<?php

namespace SocialiteProviders\BAuth;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BAuthExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('bauth', Provider::class);
    }
}
