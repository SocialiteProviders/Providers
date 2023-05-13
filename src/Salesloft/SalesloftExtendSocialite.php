<?php

namespace SocialiteProviders\Salesloft;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SalesloftExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            Provider::PROVIDER_NAME,
            Provider::class
        );
    }
}
