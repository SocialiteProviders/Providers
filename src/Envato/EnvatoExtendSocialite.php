<?php

namespace SocialiteProviders\Envato;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EnvatoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('envato', Provider::class);
    }
}
