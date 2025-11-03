<?php

namespace SocialiteProviders\Teamleader;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TeamleaderExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('teamleader', Provider::class);
    }
}
