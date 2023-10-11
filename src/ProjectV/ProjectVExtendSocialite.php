<?php

namespace SocialiteProviders\ProjectV;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ProjectVExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('projectv', Provider::class);
    }
}
