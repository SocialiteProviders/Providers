<?php

namespace SocialiteProviders\YNAB;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YNABExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('ynab', Provider::class);
    }
}