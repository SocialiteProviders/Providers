<?php

namespace SocialiteProviders\Lichess;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LichessExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('lichess', Provider::class);
    }
}
