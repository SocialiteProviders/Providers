<?php

namespace SocialiteProviders\VersionOne;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VersionOneExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('versionone', Provider::class);
    }
}
