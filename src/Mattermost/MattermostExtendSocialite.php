<?php

namespace SocialiteProviders\Mattermost;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MatterMostExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'mattermost',
            __NAMESPACE__.'\Provider'
        );
    }
}
