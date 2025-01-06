<?php

namespace SocialiteProviders\Kinde;

use SocialiteProviders\Manager\SocialiteWasCalled;

class KindeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('kinde', Provider::class);
    }
}
