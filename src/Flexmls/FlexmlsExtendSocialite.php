<?php

namespace SocialiteProviders\Flexmls;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FlexmlsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('flexmls', Provider::class);
    }
}
