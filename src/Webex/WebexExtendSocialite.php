<?php

namespace SocialiteProviders\Webex;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WebexExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('webex', Provider::class);
    }
}
