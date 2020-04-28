<?php

namespace SocialiteProviders\Bitbucket;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BitbucketExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'bitbucket',
            __NAMESPACE__.'\Provider'
        );
    }
}
