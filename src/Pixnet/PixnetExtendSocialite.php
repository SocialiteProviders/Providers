<?php

namespace SocialiteProviders\Pixnet;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PixnetExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('pixnet', Provider::class);
    }
}
