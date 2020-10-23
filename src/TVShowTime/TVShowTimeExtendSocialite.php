<?php

namespace SocialiteProviders\TVShowTime;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TVShowTimeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('tvshowtime', Provider::class);
    }
}
