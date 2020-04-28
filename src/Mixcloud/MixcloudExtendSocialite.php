<?php

namespace SocialiteProviders\Mixcloud;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MixcloudExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'mixcloud',
            __NAMESPACE__.'\Provider'
        );
    }
}
