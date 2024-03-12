<?php

namespace SocialiteProviders\Instructure;

use SocialiteProviders\Manager\SocialiteWasCalled;

class InstructureExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('instructure', Provider::class);
    }
}
