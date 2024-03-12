<?php

namespace SocialiteProviders\Weixin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeixinExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('weixin', Provider::class);
    }
}
