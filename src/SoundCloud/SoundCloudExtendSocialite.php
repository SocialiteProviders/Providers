<?php

namespace SocialiteProviders\SoundCloud;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SoundCloudExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'soundcloud', __NAMESPACE__.'\Provider'
        );
    }
}
