<?php

namespace SocialiteProviders\SapoId;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SapoIdExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('sapoid', Provider::class);
    }
}
