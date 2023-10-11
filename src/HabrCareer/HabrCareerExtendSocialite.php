<?php

namespace SocialiteProviders\HabrCareer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HabrCareerExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('habrcareer', Provider::class);
    }
}
