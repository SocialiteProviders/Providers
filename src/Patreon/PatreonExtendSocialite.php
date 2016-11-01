<?php

namespace SocialiteProviders\Patreon;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PatreonExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'patreon', __NAMESPACE__.'\Provider'
        );
    }
}
