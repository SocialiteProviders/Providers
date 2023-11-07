<?php

namespace SocialiteProviders\Medium;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MediumExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('medium', Provider::class);
    }
}
