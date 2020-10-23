<?php

namespace SocialiteProviders\GitLab;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GitLabExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('gitlab', Provider::class);
    }
}
