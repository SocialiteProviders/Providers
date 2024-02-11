<?php

namespace SocialiteProviders\TeamService;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TeamServiceExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('teamservice', Provider::class);
    }
}
