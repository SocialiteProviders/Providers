<?php

namespace SocialiteProviders\FranceConnect;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FranceConnectExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'franceconnect',
            __NAMESPACE__.'\Provider'
        );
    }
}
