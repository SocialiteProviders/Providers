<?php

namespace SocialiteProviders\MakerLog;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MakerLogExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('makerlog', Provider::class);
    }
}
