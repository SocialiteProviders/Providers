<?php

namespace SocialiteProviders\Usersau;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UsersauExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('usersau', Provider::class);
    }
}
