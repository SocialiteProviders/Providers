<?php

namespace SocialiteProviders\Salesloft;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SalesloftExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite(
            Provider::PROVIDER_NAME,
            Provider::class
        );
    }
}
