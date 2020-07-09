<?php

namespace SocialiteProviders\SciStarter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SciStarterExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('scistarter', Provider::class);
    }
}
