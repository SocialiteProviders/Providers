<?php

namespace SocialiteProviders\Douban;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DoubanExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('douban', Provider::class);
    }
}
