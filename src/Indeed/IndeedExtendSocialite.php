<?php

namespace SocialiteProviders\Indeed;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IndeedExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('indeed', Provider::class);
    }
}
