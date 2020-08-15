<?php

namespace SocialiteProviders\Streamlabs;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StreamlabsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('streamlabs', Provider::class);
    }
}
