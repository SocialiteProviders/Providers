<?php

namespace SocialiteProviders\Zoho;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZohoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('zoho', Provider::class);
    }
}
