<?php

namespace SocialiteProviders\Basecamp;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BasecampExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('basecamp', Provider::class);
    }
}
