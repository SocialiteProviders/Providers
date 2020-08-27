<?php

namespace SocialiteProviders\GitLab;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GitLabExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('gitlab', Provider::class);
    }
}
