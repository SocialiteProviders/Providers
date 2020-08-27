<?php

namespace SocialiteProviders\Mattermost;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MattermostExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mattermost', Provider::class);
    }
}
