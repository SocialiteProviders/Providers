<?php

namespace SocialiteProviders\Todoist;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TodoistExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('auth0', __NAMESPACE__.'\Provider');
    }
}
