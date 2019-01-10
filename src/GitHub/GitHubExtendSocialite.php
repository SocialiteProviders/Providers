<?php

namespace SocialiteProviders\GitHub;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GitHubExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('github', __NAMESPACE__.'\Provider');
    }
}
