<?php

namespace SocialiteProviders\AmoCRM;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AmoCRMExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('amocrm', Provider::class);
    }
}
