<?php

namespace SocialiteProviders\Salesloft;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SalesloftExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            Provider::PROVIDER_NAME,
            Provider::class
        );
    }
}
