<?php

namespace SocialiteProviders\Asana;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AsanaExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'asana', __NAMESPACE__.'\Provider'
        );
    }
}
