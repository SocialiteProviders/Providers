<?php

namespace SocialiteProviders\Pipedrive;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PipedriveExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('pipedrive', Provider::class);
    }
}
