<?php

namespace SocialiteProviders\Dribbble;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DribbbleExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('dribbble', Provider::class);
    }
}
