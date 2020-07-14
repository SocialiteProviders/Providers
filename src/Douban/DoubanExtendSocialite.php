<?php

namespace SocialiteProviders\Douban;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DoubanExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('douban', Provider::class);
    }
}
