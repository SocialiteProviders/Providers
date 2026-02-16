<?php

namespace SocialiteProviders\Tailscale;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TailscaleExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('tailscale', Provider::class);
    }
}
