<?php

namespace SocialiteProviders\Jira;

use SocialiteProviders\Manager\SocialiteWasCalled;

class JiraExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'jira', __NAMESPACE__.'\Provider', __NAMESPACE__.'\Server'
        );
    }
}
