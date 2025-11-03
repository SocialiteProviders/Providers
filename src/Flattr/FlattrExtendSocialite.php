<?php

namespace SocialiteProviders\Flattr;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FlattrExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('flattr', Provider::class);
    }
}
