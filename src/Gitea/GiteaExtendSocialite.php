<?php

namespace SocialiteProviders\Gitea;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GiteaExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('gitea', __NAMESPACE__.'\Provider');
    }
}
