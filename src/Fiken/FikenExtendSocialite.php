<?php

namespace SocialiteProviders\Fiken;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FikenExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('fiken', Provider::class);
    }
}
