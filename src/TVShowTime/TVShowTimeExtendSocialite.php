<?php

namespace SocialiteProviders\TVShowTime;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TVShowTimeExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('tvshowtime', Provider::class);
    }
}
