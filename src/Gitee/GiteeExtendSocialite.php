<?php

namespace SocialiteProviders\Gitee;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GiteeExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('gitee', Provider::class);
    }
}
