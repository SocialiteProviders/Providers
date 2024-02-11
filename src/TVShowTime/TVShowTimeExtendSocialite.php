<?php

namespace SocialiteProviders\TVShowTime;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TVShowTimeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('tvshowtime', Provider::class);
    }
}
