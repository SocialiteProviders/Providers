<?php

namespace SocialiteProviders\Atlassian;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AtlassianExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('atlassian', Provider::class);
    }
}
