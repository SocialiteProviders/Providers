<?php

namespace SocialiteProviders\JumpCloud;

use SocialiteProviders\Manager\SocialiteWasCalled;

class JumpCloudExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('jumpcloud', Provider::class);
    }
}
