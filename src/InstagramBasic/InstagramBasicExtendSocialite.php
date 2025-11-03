<?php

namespace SocialiteProviders\InstagramBasic;

use SocialiteProviders\Manager\SocialiteWasCalled;

class InstagramBasicExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('instagrambasic', Provider::class);
    }
}
