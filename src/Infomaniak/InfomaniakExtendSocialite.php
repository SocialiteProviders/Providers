<?php

namespace SocialiteProviders\Infomaniak;

use SocialiteProviders\Manager\SocialiteWasCalled;

class InfomaniakExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     * @return void
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('infomaniak', Provider::class);
    }
}
