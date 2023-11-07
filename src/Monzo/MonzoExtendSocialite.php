<?php

namespace SocialiteProviders\Monzo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MonzoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('monzo', Provider::class);
    }
}
