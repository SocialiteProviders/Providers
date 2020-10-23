<?php

namespace SocialiteProviders\Envato;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EnvatoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('envato', Provider::class);
    }
}
