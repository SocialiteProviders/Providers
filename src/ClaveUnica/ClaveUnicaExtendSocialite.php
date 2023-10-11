<?php

namespace SocialiteProviders\ClaveUnica;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ClaveUnicaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('claveunica', Provider::class);
    }
}
