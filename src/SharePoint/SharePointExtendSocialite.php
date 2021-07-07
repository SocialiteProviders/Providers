<?php

namespace SocialiteProviders\SharePoint;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SharePointExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('sharepoint', Provider::class);
    }
}
