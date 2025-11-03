<?php

namespace SocialiteProviders\Klarna;

use SocialiteProviders\Manager\SocialiteWasCalled;

class KlarnaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('klarna', Provider::class);
    }
}
