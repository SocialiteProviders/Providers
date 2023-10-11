<?php

namespace SocialiteProviders\SuperOffice;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SuperOfficeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('superoffice', Provider::class);
    }
}
