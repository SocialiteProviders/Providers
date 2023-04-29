<?php

namespace SocialiteProviders\Onelogin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OneloginExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('onelogin', Provider::class);
    }
}
