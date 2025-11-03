<?php

namespace SocialiteProviders\AngelList;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AngelListExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('angellist', Provider::class);
    }
}
