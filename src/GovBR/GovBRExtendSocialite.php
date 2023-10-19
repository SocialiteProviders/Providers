<?php

namespace SocialiteProviders\GovBR;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GovBRExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('govbr', Provider::class);
    }
}
