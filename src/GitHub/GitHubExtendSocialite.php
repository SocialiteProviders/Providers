<?php

namespace SocialiteProviders\GitHub;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GitHubExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('github', Provider::class);
    }
}
