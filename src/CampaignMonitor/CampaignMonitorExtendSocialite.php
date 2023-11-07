<?php

namespace SocialiteProviders\CampaignMonitor;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CampaignMonitorExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('campaignmonitor', Provider::class);
    }
}
