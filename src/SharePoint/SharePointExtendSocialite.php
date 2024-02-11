<?php

namespace SocialiteProviders\SharePoint;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SharePointExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('sharepoint', Provider::class);
    }
}
