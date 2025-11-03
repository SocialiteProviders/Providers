<?php

namespace SocialiteProviders\LinkedIn;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LinkedInExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('linkedin', Provider::class);
    }
}
