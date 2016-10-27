<?php

namespace SocialiteProviders\Weibo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeiboExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('weibo', __NAMESPACE__.'\Provider');
    }
}
