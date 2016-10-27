<?php

namespace SocialiteProviders\Spotify;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SpotifyExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'spotify', __NAMESPACE__.'\Provider'
        );
    }
}
