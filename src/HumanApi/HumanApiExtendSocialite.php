<?php

namespace SocialiteProviders\HumanApi;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HumanApiExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('humanapi', Provider::class);
    }
}
