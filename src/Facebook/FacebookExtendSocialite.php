<?php

namespace SocialiteProviders\Facebook;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FacebookExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('facebook', Provider::class);
    }
}
