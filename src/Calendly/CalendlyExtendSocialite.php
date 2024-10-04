<?php

namespace SocialiteProviders\Calendly;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CalendlyExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('calendly', Provider::class);
    }
}
