<?php

namespace SocialiteProviders\Gitee;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GiteeExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('gitee', __NAMESPACE__.'\Provider');
    }
}
