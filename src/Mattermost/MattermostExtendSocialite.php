<?php

namespace SocialiteProviders\Mattermost;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MattermostExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mattermost', Provider::class);
    }
}
