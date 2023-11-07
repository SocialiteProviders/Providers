<?php

namespace SocialiteProviders\GitHub;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GitHubExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('github', Provider::class);
    }
}
