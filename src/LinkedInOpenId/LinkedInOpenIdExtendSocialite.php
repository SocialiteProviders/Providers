<?php

namespace SocialiteProviders\LinkedInOpenId;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LinkedInOpenIdExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('linkedin-openid', Provider::class);
    }
}
