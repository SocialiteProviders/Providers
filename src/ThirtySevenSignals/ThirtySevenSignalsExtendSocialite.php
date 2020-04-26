<?php

namespace SocialiteProviders\ThirtySevenSignals;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ThirtySevenSignalsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            '37signals', __NAMESPACE__.'\Provider'
        );
    }
}
