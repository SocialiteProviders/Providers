<?php

namespace SocialiteProviders\Bitrix24;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Bitrix24ExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('bitrix24', Provider::class);
    }
}
