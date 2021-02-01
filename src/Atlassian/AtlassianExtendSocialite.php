<?php

namespace SocialiteProviders\Atlassian;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AtlassianExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('atlassian', Provider::class);
    }
}
