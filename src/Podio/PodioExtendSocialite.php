<?php

namespace SocialiteProviders\Podio;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PodioExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('podio', Provider::class);
    }
}
