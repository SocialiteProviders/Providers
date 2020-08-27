<?php

namespace SocialiteProviders\Reddit;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RedditExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('reddit', Provider::class);
    }
}
