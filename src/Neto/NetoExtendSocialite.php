<?php

namespace SocialiteProviders\Neto;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NetoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('neto', Provider::class);
    }
}
