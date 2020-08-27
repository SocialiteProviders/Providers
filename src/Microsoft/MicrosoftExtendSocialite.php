<?php

namespace SocialiteProviders\Microsoft;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MicrosoftExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('microsoft', Provider::class);
    }
}
