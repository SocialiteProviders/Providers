<?php

namespace SocialiteProviders\HumanApi;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HumanApiExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'humanapi',
            __NAMESPACE__.'\Provider'
        );
    }
}
