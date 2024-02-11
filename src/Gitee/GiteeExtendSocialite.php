<?php

namespace SocialiteProviders\Gitee;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GiteeExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('gitee', Provider::class);
    }
}
