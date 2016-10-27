<?php

namespace SocialiteProviders\YouTube;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YouTubeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'youtube', __NAMESPACE__.'\Provider'
        );
    }
}
