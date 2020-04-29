<?php

namespace SocialiteProviders\GameWisp;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GameWispExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'gamewisp',
            __NAMESPACE__.'\Provider'
        );
    }
}
