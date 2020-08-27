<?php

namespace SocialiteProviders\HabrCareer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HabrCareerExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('habrcareer', Provider::class);
    }
}
