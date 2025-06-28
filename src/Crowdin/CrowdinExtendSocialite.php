<?php

namespace SocialiteProviders\Crowdin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CrowdinExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('crowdin', Provider::class);
    }
}
