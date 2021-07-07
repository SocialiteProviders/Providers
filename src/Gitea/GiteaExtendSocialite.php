<?php

namespace SocialiteProviders\Gitea;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GiteaExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('gitea', Provider::class);
    }
}
