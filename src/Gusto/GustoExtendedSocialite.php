<?php

namespace SocialiteProviders\Gusto;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GustoExtendedSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('gusto', Provider::class);
    }
}
