<?php

namespace SocialiteProviders\Frappe;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FrappeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('frappe', Provider::class);
    }
}
