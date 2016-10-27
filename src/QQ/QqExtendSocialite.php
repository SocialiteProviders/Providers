<?php

namespace SocialiteProviders\QQ;

use SocialiteProviders\Manager\SocialiteWasCalled;

class QqExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('qq', __NAMESPACE__.'\Provider');
    }
}
