<?php

namespace SocialiteProviders\Aikido;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AikidoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('aikido', Provider::class);
    }
}
