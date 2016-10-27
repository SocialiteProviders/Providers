<?php

namespace SocialiteProviders\Twitch;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TwitchExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'twitch', __NAMESPACE__.'\Provider'
        );
    }
}
