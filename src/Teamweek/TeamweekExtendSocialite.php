<?php

namespace SocialiteProviders\Teamweek;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TeamweekExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('teamweek', Provider::class);
    }
}
