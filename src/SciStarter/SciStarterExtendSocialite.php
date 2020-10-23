<?php

namespace SocialiteProviders\SciStarter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SciStarterExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('scistarter', Provider::class);
    }
}
