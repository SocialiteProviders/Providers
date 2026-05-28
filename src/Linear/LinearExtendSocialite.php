<?php

namespace SocialiteProviders\Linear;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LinearExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     * @return void
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('linear', Provider::class);
    }
}
