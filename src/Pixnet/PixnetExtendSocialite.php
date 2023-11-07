<?php

namespace SocialiteProviders\Pixnet;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PixnetExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('pixnet', Provider::class);
    }
}
