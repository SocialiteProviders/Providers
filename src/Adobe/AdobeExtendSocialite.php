<?php

namespace SocialiteProviders\Adobe;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AdobeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('adobe', Provider::class);
    }
}
