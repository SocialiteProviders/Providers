<?php

namespace SocialiteProviders\Bitly;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BitlyExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('bitly', Provider::class);
    }
}
