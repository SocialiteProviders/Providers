<?php

namespace SocialiteProviders\Teamleader;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TeamleaderExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('teamleader', Provider::class);
    }
}
