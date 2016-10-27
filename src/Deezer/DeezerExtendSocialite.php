<?php

namespace SocialiteProviders\Deezer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DeezerExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'deezer', __NAMESPACE__.'\Provider'
        );
    }
}
