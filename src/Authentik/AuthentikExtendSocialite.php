<?php

namespace SocialiteProviders\Authentik;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AuthentikExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('authentik', Provider::class);
    }
}
