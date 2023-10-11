<?php

namespace SocialiteProviders\AzureADB2C;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AzureADB2CExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('azureadb2c', Provider::class);
    }
}
