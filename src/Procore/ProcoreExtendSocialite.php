<?php

namespace SocialiteProviders\Procore;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ProcoreExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('procore', Provider::class);
    }
}
