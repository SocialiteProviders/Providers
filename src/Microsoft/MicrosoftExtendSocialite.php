<?php

namespace SocialiteProviders\Microsoft;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MicrosoftExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('microsoft', Provider::class);
    }
}
