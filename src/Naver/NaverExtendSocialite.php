<?php

namespace SocialiteProviders\Naver;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NaverExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('naver', Provider::class);
    }
}
