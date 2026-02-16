<?php

namespace SocialiteProviders\Trovo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TrovoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('trovo', Provider::class);
    }
}
