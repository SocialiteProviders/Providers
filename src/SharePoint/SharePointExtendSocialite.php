<?php

namespace SocialiteProviders\SharePoint;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SharePointExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('sharepoint', __NAMESPACE__.'\Provider');
    }
}
