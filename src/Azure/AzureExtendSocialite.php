<?php

namespace SocialiteProviders\Azure;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AzureExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('azure', Provider::class);
    }
}
