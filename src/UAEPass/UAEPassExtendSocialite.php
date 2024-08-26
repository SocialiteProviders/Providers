<?php

namespace SocialiteProviders\UAEPass;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UAEPassExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('uaepass', Provider::class);
    }
}
