<?php

namespace SocialiteProviders\Facebook;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FacebookExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('facebook', Provider::class);
    }
}
