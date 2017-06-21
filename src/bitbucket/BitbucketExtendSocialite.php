<?php

namespace SocialiteProviders\Bitbucket;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BitbucketExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('bitbucket', __NAMESPACE__.'\Provider');
    }
}
