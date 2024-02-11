<?php

namespace SocialiteProviders\SciStarter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SciStarterExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('scistarter', Provider::class);
    }
}
