<?php

namespace SocialiteProviders\GitLab;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GitLabExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('gitlab', Provider::class);
    }
}
