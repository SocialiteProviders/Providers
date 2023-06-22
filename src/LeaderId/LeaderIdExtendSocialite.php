<?php

namespace SocialiteProviders\LeaderId;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LeaderIdExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('leader-id', Provider::class);
    }
}
