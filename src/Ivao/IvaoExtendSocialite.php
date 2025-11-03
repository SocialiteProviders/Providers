<?php

namespace SocialiteProviders\Ivao;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IvaoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('ivao', Provider::class);
    }
}
