<?php

namespace SocialiteProviders\QQ;

use SocialiteProviders\Manager\SocialiteWasCalled;

class QqExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('qq', Provider::class);
    }
}
