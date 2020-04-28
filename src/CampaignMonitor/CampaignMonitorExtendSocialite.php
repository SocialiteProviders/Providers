<?php

namespace SocialiteProviders\CampaignMonitor;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CampaignMonitorExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'campaignmonitor',
            __NAMESPACE__.'\Provider'
        );
    }
}
