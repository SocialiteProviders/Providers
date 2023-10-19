<?php

namespace SocialiteProviders\Webflow;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WebflowExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('webflow', Provider::class);
    }
}
