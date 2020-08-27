<?php

namespace SocialiteProviders\Pixnet;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PixnetExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('pixnet', Provider::class);
    }
}
