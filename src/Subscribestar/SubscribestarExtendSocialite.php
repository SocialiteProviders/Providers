<?php

namespace SocialiteProviders\Subscribestar;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SubscribestarExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('subscribestar', Provider::class);
    }
}
