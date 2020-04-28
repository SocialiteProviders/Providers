<?php

namespace SocialiteProviders\AngelList;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AngelListExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'angellist',
            __NAMESPACE__.'\Provider'
        );
    }
}
