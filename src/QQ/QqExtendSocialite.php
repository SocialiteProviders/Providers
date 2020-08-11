<?php

namespace SocialiteProviders\QQ;

use SocialiteProviders\Manager\SocialiteWasCalled;

class QqExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('qq', Provider::class);
    }
}
