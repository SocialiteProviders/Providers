<?php

namespace SocialiteProviders\Instagram;

use SocialiteProviders\Manager\SocialiteWasCalled;

class InstagramExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('instagram', Provider::class);
    }
}
