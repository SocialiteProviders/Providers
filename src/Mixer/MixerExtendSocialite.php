<?php

namespace SocialiteProviders\Mixer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MixerExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'mixer', __NAMESPACE__.'\Provider'
        );
    }
}
