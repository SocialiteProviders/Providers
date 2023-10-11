<?php

namespace SocialiteProviders\Mixcloud;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MixcloudExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('mixcloud', Provider::class);
    }
}
