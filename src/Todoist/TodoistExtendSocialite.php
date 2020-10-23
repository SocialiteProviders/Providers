<?php

namespace SocialiteProviders\Todoist;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TodoistExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('todoist', Provider::class);
    }
}
