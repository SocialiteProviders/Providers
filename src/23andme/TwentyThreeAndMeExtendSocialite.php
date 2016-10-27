<?php

namespace SocialiteProviders\TwentyThreeAndMe;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TwentyThreeAndMeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            '23andme', __NAMESPACE__.'\Provider'
        );
    }
}
