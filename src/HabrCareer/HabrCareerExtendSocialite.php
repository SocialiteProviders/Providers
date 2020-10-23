<?php

namespace SocialiteProviders\HabrCareer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HabrCareerExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('habrcareer', Provider::class);
    }
}
