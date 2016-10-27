<?php

namespace SocialiteProviders\Slack;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SlackExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'slack', __NAMESPACE__.'\Provider'
        );
    }
}
