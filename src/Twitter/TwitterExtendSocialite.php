<?php

namespace SocialiteProviders\Twitter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TwitterExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'twitter',
            __NAMESPACE__.'\Provider',
            __NAMESPACE__.'\Server'
        );
    }
}
