<?php

namespace SocialiteProviders\Amazon;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AmazonExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('amazon', Provider::class);
    }
}
