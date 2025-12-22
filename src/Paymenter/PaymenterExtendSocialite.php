<?php

namespace SocialiteProviders\Paymenter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PaymenterExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('paymenter', Provider::class);
    }
}
